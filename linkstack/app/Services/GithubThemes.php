<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GithubThemes
{
    private const STATE = 'github-themes.json';
    private array $catalogues = [];
    private array $trees = [];

    public function state(): array
    {
        $defaults = [
            'repository' => config('theme-repository.repository'),
            'private' => false,
            'installed' => [],
            'uploaded' => [],
        ];
        if (!Storage::disk('local')->exists(self::STATE)) return $defaults;
        $saved = json_decode(Storage::disk('local')->get(self::STATE), true);
        return is_array($saved) ? array_merge($defaults, $saved) : $defaults;
    }

    public function save(array $state): void
    {
        if (!Storage::disk('local')->put(self::STATE, json_encode($state, JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Falha ao salvar configuração.');
        }
        @chmod(storage_path('app/'.self::STATE), 0600);
    }

    public function api(array $state, string $path, bool $raw = false)
    {
        $http = Http::timeout(40)->connectTimeout(10)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders([
                'Accept' => $raw ? 'application/vnd.github.raw+json' : 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'LinkFree-Themes',
            ]);
        if (!empty($state['token'])) $http = $http->withToken(Crypt::decryptString($state['token']));
        $response = $http->get('https://api.github.com/repos/'.$state['repository'].$path);
        if (!$response->successful()) {
            throw new RuntimeException('GitHub indisponível ou acesso negado (HTTP '.$response->status().').');
        }
        return $raw ? $response->body() : $response->json();
    }

    private function treeCacheKey(array $state): string
    {
        return 'github-theme-tree:'.hash('sha256', $state['repository'].($state['token'] ?? ''));
    }

    public function refresh(array $state): void
    {
        Cache::forget($this->treeCacheKey($state));
        $this->trees = [];
        $this->catalogues = [];
    }

    private function tree(array $state): array
    {
        $key = $this->treeCacheKey($state);
        if (isset($this->trees[$key])) return $this->trees[$key];
        return $this->trees[$key] = Cache::remember($key, now()->addMinutes(3), function () use ($state) {
            $repo = $this->api($state, '');
            $commit = $this->api($state, '/commits/'.rawurlencode($repo['default_branch']));
            $tree = $this->api($state, '/git/trees/'.$commit['commit']['tree']['sha'].'?recursive=1');
            if (!empty($tree['truncated'])) throw new RuntimeException('Catálogo muito grande para listar com segurança.');
            return ['revision' => $commit['sha'], 'files' => $tree['tree'] ?? []];
        });
    }

    public function catalogue(array $state, string $category): array
    {
        if (!array_key_exists($category, config('theme-repository.categories'))) {
            throw new RuntimeException('Categoria inválida.');
        }
        $key = hash('sha256', $state['repository'].($state['token'] ?? '').$category);
        if (isset($this->catalogues[$key])) return $this->catalogues[$key];

        $tree = $this->tree($state);
        $themes = [];
        foreach ($tree['files'] as $file) {
            $parts = explode('/', $file['path']);
            if (count($parts) !== 3 || $parts[0] !== $category || $file['type'] !== 'blob') continue;
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $parts[1])) continue;
            $name = $parts[1];
            if (str_ends_with(strtolower($parts[2]), '.zip')) $themes[$name]['packages'][] = $file;
            if (in_array(strtolower($parts[2]), ['thumbnail.jpg', 'thumbnail.png', 'thumbnail.jpeg'], true)) {
                $themes[$name]['thumbnail'] = $file;
            }
        }

        $result = [];
        foreach ($themes as $name => $theme) {
            if (count($theme['packages'] ?? []) !== 1 || empty($theme['thumbnail'])) continue;
            $zip = $theme['packages'][0];
            $identity = $state['repository'].'/'.$category.'/'.$name;
            $installed = $state['installed'][$identity] ?? null;
            if (!$installed && isset($state['uploaded'][$name])) {
                $installed = [
                    'sha' => $state['uploaded'][$name],
                    'directory' => $name,
                    'source' => 'upload',
                ];
            }
            if ($installed && !is_dir(base_path('themes/'.$installed['directory']))) $installed = null;
            $current = $installed && hash_equals((string) $installed['sha'], (string) $zip['sha']);
            $result[] = [
                'name' => $name,
                'zip' => $zip,
                'thumbnail' => $theme['thumbnail'],
                'revision' => $tree['revision'],
                'current' => (bool) $current,
                'installed' => $installed,
                'update_available' => (bool) ($installed && !$current),
            ];
        }
        return $this->catalogues[$key] = $result;
    }

    public function updates(array $state): array
    {
        $updates = [];
        foreach (array_keys(config('theme-repository.categories')) as $category) {
            foreach ($this->catalogue($state, $category) as $theme) {
                if ($theme['update_available']) {
                    $updates[] = ['name' => $theme['name'], 'category' => $category];
                }
            }
        }
        return $updates;
    }

    public function theme(array $state, string $category, string $name): array
    {
        foreach ($this->catalogue($state, $category) as $theme) {
            if ($theme['name'] === $name) return $theme;
        }
        throw new RuntimeException('Tema não encontrado.');
    }

    public function registerUpload(string $directory, string $archive): void
    {
        $state = $this->state();
        foreach ($state['installed'] as $key => $entry) {
            if (($entry['directory'] ?? null) === $directory) unset($state['installed'][$key]);
        }
        $size = filesize($archive);
        $bytes = file_get_contents($archive);
        if ($bytes === false) throw new RuntimeException('Não foi possível ler o ZIP.');
        $state['uploaded'][$directory] = sha1('blob '.$size."\0".$bytes);
        $this->save($state);
    }

    public function unregister(string $directory): void
    {
        $state = $this->state();
        unset($state['uploaded'][$directory]);
        foreach ($state['installed'] as $key => $entry) {
            if (($entry['directory'] ?? null) === $directory) unset($state['installed'][$key]);
        }
        $this->save($state);
    }

    public function download(array $state, array $file, string $revision): string
    {
        if (($file['size'] ?? 0) > 25 * 1024 * 1024) throw new RuntimeException('Arquivo excede 25 MB.');
        $path = implode('/', array_map('rawurlencode', explode('/', $file['path'])));
        $bytes = $this->api($state, '/contents/'.$path.'?ref='.rawurlencode($revision), true);
        if (strlen($bytes) > 25 * 1024 * 1024 || sha1('blob '.strlen($bytes)."\0".$bytes) !== $file['sha']) {
            throw new RuntimeException('Integridade do arquivo não confirmada.');
        }
        return $bytes;
    }
}
