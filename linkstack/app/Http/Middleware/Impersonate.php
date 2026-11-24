<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Closure;

class Impersonate
{
    public function handle($request, Closure $next)
    {
        $state = $request->session()->get('linkfree_impersonation');
        if (!$state) {
            return $next($request);
        }

        $member = Auth::user();
        $admin = User::find($state['admin_id'] ?? null);
        if (!$member || (int) $member->id !== (int) ($state['member_id'] ?? 0) ||
            !in_array($member->role, ['user', 'vip'], true) ||
            !$admin || $admin->role !== 'admin' || $admin->block === 'yes') {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login');
        }

        $response = $next($request);
        // Only inject into HTML, not redirects, JSON or streamed files.
        if ($response instanceof \Illuminate\Http\Response &&
            $response->getStatusCode() === 200 &&
            str_contains(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            $html = view('components.impersonation-bar', ['member' => $member])->render();
            $response->setContent(preg_replace_callback('/<body\b[^>]*>/i', fn ($m) => $m[0] . $html, $response->getContent(), 1));
        }
        return $response;
    }
}
