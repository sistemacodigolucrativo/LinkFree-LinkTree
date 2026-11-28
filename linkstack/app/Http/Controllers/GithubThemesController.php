<?php
namespace App\Http\Controllers;

use App\Services\GithubThemes;
use App\Services\ThemeInstaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class GithubThemesController extends Controller
{
    private function updatesEnabled(): bool
    {
        return filter_var(env('ENABLE_THEME_UPDATER', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function index(Request $request, GithubThemes $service)
    {
        if ($request->boolean('updates') && !$this->updatesEnabled()) {
            return redirect()->route('githubThemes')->withErrors(['theme' => 'O atualizador de temas está desativado nas configurações.']);
        }
        $s = $service->state();
        $category = $request->query('category', 'Free');
        $themes = []; $updates = []; $error = null;
        try {
            if ($request->boolean('updates')) $service->refresh($s);
            $themes = $service->catalogue($s, $category);
            if ($request->boolean('updates')) $updates = $service->updates($s);
        }
        catch (\Throwable $e) { $error = 'Não foi possível consultar o catálogo. Confira o repositório, as permissões e o limite de acesso do GitHub.'; }
        return view('panel.github-themes', ['repository' => $s['repository'], 'private' => $s['private'], 'hasToken' => !empty($s['token']), 'category' => $category, 'themes' => $themes, 'updates' => $updates, 'checkingUpdates' => $request->boolean('updates'), 'catalogueError' => $error, 'updatesEnabled' => $this->updatesEnabled()]);
    }
    public function status(GithubThemes $service)
    {
        if (!$this->updatesEnabled()) return response()->json(['error' => 'O atualizador de temas está desativado.'], 403)->header('Cache-Control', 'private, no-store');
        try {
            $updates = $service->updates($service->state());
            return response()->json(['count' => count($updates), 'updates' => $updates])
                ->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Não foi possível verificar as atualizações.'], 503)
                ->header('Cache-Control', 'private, no-store');
        }
    }

    public function configure(Request $request, GithubThemes $service)
    {
        $data = $request->validate(['repository' => ['required','regex:~^https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+/?$~'], 'private' => 'required|boolean', 'token' => 'nullable|string|max:512']);
        try {
            return Cache::lock('github-themes-write', 180)->block(5, function () use ($service, $data) {
                $s = $service->state();
                $repository = preg_replace('/\.git$/', '', trim(substr($data['repository'], 19), '/'));
                $token = $repository === $s['repository'] ? ($s['token'] ?? null) : null;
                if (!empty($data['token'])) $token = Crypt::encryptString($data['token']);
                if (!$data['private']) $token = null;
                if ($data['private'] && !$token) throw new \RuntimeException();
                $s = array_merge($s, ['repository' => $repository, 'private' => (bool)$data['private'], 'token' => $token]);
                $service->api($s, '');
                $service->save($s);
                return redirect()->route('githubThemes')->with('success', 'Repositório validado e salvo.');
            });
        } catch (\Throwable $e) {
            return redirect()->route('githubThemes')->withErrors(['repository' => 'Não foi possível validar ou salvar. Para repositório privado, use um token com acesso somente de leitura a Contents neste repositório.']);
        }
    }
    public function thumbnail(string $category, string $name, GithubThemes $service)
    {
        try {
            $s = $service->state();
            $theme = $service->theme($s, $category, $name);
            $cacheKey = 'github-theme-thumbnail:'.hash('sha256', $s['repository'].$theme['thumbnail']['sha']);
            $bytes = Cache::remember($cacheKey, now()->addHours(6), function () use ($service, $s, $theme) {
                return $service->download($s, $theme['thumbnail'], $theme['revision']);
            });
            $info = @getimagesizefromstring($bytes);
            if (!$info || !in_array($info['mime'], ['image/png','image/jpeg'], true)) abort(422);
            return response($bytes)->header('Content-Type', $info['mime'])->header('Cache-Control', 'private, max-age=300')->header('X-Content-Type-Options', 'nosniff');
        } catch (\Throwable $e) { abort(404); }
    }
    public function install(string $category, string $name, GithubThemes $service, ThemeInstaller $installer)
    {
        try {
            return Cache::lock('github-themes-write', 180)->block(5, function () use ($category, $name, $service, $installer) {
                $s = $service->state();
                $theme = $service->theme($s, $category, $name);
                if ($theme['installed'] && !$this->updatesEnabled()) {
                    return back()->withErrors(['theme' => 'O atualizador de temas está desativado nas configurações.']);
                }
                if ($theme['current']) return back()->with('success', 'O tema já está atualizado.');
                $directory = $theme['installed']['directory'] ?? preg_replace('/[0-9.-]/', '', $name);
                if (!$theme['installed'] && file_exists(base_path('themes/'.$directory))) throw new \RuntimeException('collision');
                $bytes = $service->download($s, $theme['zip'], $theme['revision']);
                $temp = tempnam(storage_path('app'), 'github-theme-');
                try {
                    file_put_contents($temp, $bytes);
                    $directory = $installer->install($temp, $directory);
                    $s['installed'][$s['repository'].'/'.$category.'/'.$name] = ['sha' => $theme['zip']['sha'], 'revision' => $theme['revision'], 'directory' => $directory, 'category' => $category];
                    unset($s['uploaded'][$directory]);
                    $service->save($s);
                } finally { @unlink($temp); }
                return back()->with('success', 'Tema instalado e revisão registrada.');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['theme' => 'Instalação não concluída. Verifique o acesso, o pacote e se já existe um tema de outra origem com o mesmo nome.']);
        }
    }
}
