<?php
namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GithubThemes
{
    private array $catalogues = [];
    private const STATE = 'github-themes.json';
    public function state(): array
    {
        return Storage::disk('local')->exists(self::STATE) ? json_decode(Storage::disk('local')->get(self::STATE), true, 512, JSON_THROW_ON_ERROR) : ['repository' => config('theme-repository.repository'), 'private' => false, 'installed' => []];
    }
    public function save(array $state): void
    {
        if (!Storage::disk('local')->put(self::STATE, json_encode($state, JSON_THROW_ON_ERROR))) throw new RuntimeException('Falha ao salvar configuração.');
        chmod(storage_path('app/'.self::STATE), 0600);
    }
    public function api(array $state, string $path, bool $raw = false)
    {
        $http = Http::timeout(40)->connectTimeout(10)->withOptions(['allow_redirects' => false])->withHeaders(['Accept' => $raw ? 'application/vnd.github.raw+json' : 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'LinkFree-Themes']);
        if (!empty($state['token'])) $http = $http->withToken(Crypt::decryptString($state['token']));
        $response = $http->get('https://api.github.com/repos/'.$state['repository'].$path);
        if (!$response->successful()) throw new RuntimeException('GitHub indisponível ou acesso negado (HTTP '.$response->status().').');
        return $raw ? $response->body() : $response->json();
    }
    public function catalogue(array $s, string $category): array
    {
        if (!array_key_exists($category, config('theme-repository.categories'))) throw new RuntimeException('Categoria inválida.');
        $cacheKey = hash('sha256', $s['repository'].($s['token'] ?? '').$category);
        if (isset($this->catalogues[$cacheKey])) return $this->catalogues[$cacheKey];
        $repo = $this->api($s, '');
        $tree = $this->api($s, '/git/trees/'.rawurlencode($repo['default_branch']).'?recursive=1');
        if (!empty($tree['truncated'])) throw new RuntimeException('Catálogo muito grande para listar com segurança.');
        $themes = [];
        foreach ($tree['tree'] as $file) {
            $parts = explode('/', $file['path']);
            if (count($parts) !== 3 || $parts[0] !== $category || $file['type'] !== 'blob') continue;
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $parts[1])) continue;
            $key = $parts[1];
            if (str_ends_with(strtolower($parts[2]), '.zip')) $themes[$key]['packages'][] = $file;
            if (in_array(strtolower($parts[2]), ['thumbnail.jpg', 'thumbnail.png', 'thumbnail.jpeg'])) $themes[$key]['thumbnail'] = $file;
        }
        $result = [];
        foreach ($themes as $name => $theme) {
            if (count($theme['packages'] ?? []) !== 1 || empty($theme['thumbnail'])) continue;
            $zip = $theme['packages'][0];
            $installed = $s['installed'][$s['repository'].'/'.$category.'/'.$name] ?? null;
            $result[] = ['name' => $name, 'zip' => $zip, 'thumbnail' => $theme['thumbnail'], 'revision' => $tree['sha'], 'current' => $installed && $installed['sha'] === $zip['sha'] && is_dir(base_path('themes/'.$installed['directory'])), 'installed' => $installed];
        }
        return $this->catalogues[$cacheKey] = $result;
    }
    public function theme(array $s, string $category, string $name): array
    {
        foreach ($this->catalogue($s, $category) as $theme) if ($theme['name'] === $name) return $theme;
        throw new RuntimeException('Tema não encontrado.');
    }
    public function download(array $s, array $file, string $revision): string
    {
        if (($file['size'] ?? 0) > 25 * 1024 * 1024) throw new RuntimeException('Arquivo excede 25 MB.');
        $path = implode('/', array_map('rawurlencode', explode('/', $file['path'])));
        $bytes = $this->api($s, '/contents/'.$path.'?ref='.rawurlencode($revision), true);
        if (strlen($bytes) > 25 * 1024 * 1024 || sha1('blob '.strlen($bytes)."\0".$bytes) !== $file['sha']) throw new RuntimeException('Integridade do arquivo não confirmada.');
        return $bytes;
    }
}
