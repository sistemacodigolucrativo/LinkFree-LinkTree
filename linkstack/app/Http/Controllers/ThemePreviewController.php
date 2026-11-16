<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemePreviewController extends Controller
{
    public function show(Request $request, string $theme)
    {
        if ($theme !== 'default' && (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $theme)
            || !is_file(base_path('themes/'.$theme.'/config.php')))) {
            abort(404);
        }
        $variant = $request->query('variant') === 'media' ? 'media' : 'links';
        return response()->view('studio.theme-demo', compact('theme', 'variant'))
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
