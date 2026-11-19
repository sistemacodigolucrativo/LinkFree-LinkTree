<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Real JPEG previews for social crawlers. Does not fetch remote URLs or expose
 * private profile data. An avatar is read only from the site's own avatar folder.
 */
class SocialPreviewController extends Controller
{
    // Keep the widescreen variant for the Home and Twitter/X large cards.
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    // The main Open Graph image for profiles is portrait for larger mobile previews.
    private const PROFILE_WIDTH = 1080;
    private const PROFILE_HEIGHT = 1350;

    public function site(Request $request)
    {
        return $this->image($request, config('app.name', 'LinkFree'),
            'Todos os seus links em um so lugar', null, false);
    }

    public function profile(Request $request, int $id)
    {
        $user = User::query()->select(
            'id', 'name', 'littlelink_description', 'littlelink_name', 'block', 'updated_at'
        )->findOrFail($id);

        if ($user->block === 'yes' || empty($user->littlelink_name)) {
            abort(404);
        }

        $avatar = findAvatar($user->id);
        $avatarPath = null;
        if ($avatar !== 'error.error') {
            $candidate = base_path($avatar);
            $avatarRoot = realpath(base_path('assets/img'));
            $resolved = realpath($candidate);
            if ($avatarRoot && $resolved && strncmp($resolved, $avatarRoot . DIRECTORY_SEPARATOR,
                strlen($avatarRoot) + 1) === 0 && is_file($resolved)) {
                $avatarPath = $resolved;
            }
        }

        $description = html_entity_decode(
            strip_tags((string) $user->littlelink_description),
            ENT_QUOTES | ENT_HTML5, 'UTF-8'
        );

        return $this->image($request, (string) $user->name,
            $description ?: 'Confira meus links e canais de contato', $avatarPath, true);
    }

    /**
     * Widescreen alternative for platforms with a fixed landscape card ratio.
     * Uses the same public data and permissions as the Open Graph portrait.
     */
    public function profileWide(Request $request, int $id)
    {
        $user = User::query()->select(
            'id', 'name', 'littlelink_description', 'littlelink_name', 'block', 'updated_at'
        )->findOrFail($id);

        if ($user->block === 'yes' || empty($user->littlelink_name)) {
            abort(404);
        }

        $avatar = findAvatar($user->id);
        $avatarPath = null;
        if ($avatar !== 'error.error') {
            $avatarRoot = realpath(base_path('assets/img'));
            $candidate = realpath(base_path($avatar));
            if ($avatarRoot && $candidate
                && strncmp($candidate, $avatarRoot . DIRECTORY_SEPARATOR,
                    strlen($avatarRoot) + 1) === 0 && is_file($candidate)) {
                $avatarPath = $candidate;
            }
        }

        $description = html_entity_decode(
            strip_tags((string) $user->littlelink_description),
            ENT_QUOTES | ENT_HTML5, 'UTF-8'
        );
        return $this->image($request, (string) $user->name,
            $description ?: 'Confira meus links e canais de contato', $avatarPath, false);
    }

    private function image(Request $request, string $name, string $description, ?string $avatarPath, bool $portrait)
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            abort(503, 'GD image extension is required for social previews.');
        }

        $version = $avatarPath ? ((string) (@filemtime($avatarPath) ?: 0)) : 'brand';
        // Changing the format changes the ETag, even if no user data changed.
        $fingerprint = hash('sha256', 'social-card-v2|' . ($portrait ? 'portrait' : 'landscape')
            . '|' . $name . '|' . $description . '|' . $version);
        $etag = '"' . substr($fingerprint, 0, 32) . '"';
        $headers = [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600, must-revalidate',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        $width = $portrait ? self::PROFILE_WIDTH : self::WIDTH;
        $height = $portrait ? self::PROFILE_HEIGHT : self::HEIGHT;
        $canvas = imagecreatetruecolor($width, $height);
        if (!$canvas) {
            abort(503, 'Unable to allocate social preview canvas.');
        }

        if ($portrait) {
            $this->drawPortrait($canvas, $name, $description, $avatarPath);
        } else {
        $background = imagecolorallocate($canvas, 10, 17, 13);
        $panel = imagecolorallocate($canvas, 21, 38, 27);
        $green = imagecolorallocate($canvas, 57, 247, 19);
        $white = imagecolorallocate($canvas, 246, 250, 244);
        $muted = imagecolorallocate($canvas, 179, 194, 181);
        $stroke = imagecolorallocate($canvas, 49, 78, 54);
        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $background);
        imagefilledrectangle($canvas, 36, 35, 1164, 594, $panel);
        imagerectangle($canvas, 36, 35, 1164, 594, $stroke);

        // Large photo area; portrait avatars keep their aspect ratio.
        imagefilledrectangle($canvas, 58, 57, 519, 572, $background);
        $photo = $this->loadAvatar($avatarPath);
        if ($photo) {
            $photoWidth = imagesx($photo);
            $photoHeight = imagesy($photo);
            $scale = min(458 / $photoWidth, 512 / $photoHeight);
            $drawWidth = max(1, (int) round($photoWidth * $scale));
            $drawHeight = max(1, (int) round($photoHeight * $scale));
            imagecopyresampled($canvas, $photo,
                60 + (458 - $drawWidth) / 2, 59 + (512 - $drawHeight) / 2,
                0, 0, $drawWidth, $drawHeight, $photoWidth, $photoHeight);
            imagedestroy($photo);
        } else {
            imagefilledellipse($canvas, 290, 305, 230, 230, $green);
            imagefilledellipse($canvas, 290, 305, 185, 185, $background);
            $this->drawText($canvas, 'LF', 64, 247, 325, $white, true);
        }

        $cleanName = $this->cleanText($name, 90);
        $cleanDescription = $this->cleanText($description, 240);

        $this->drawText($canvas, 'LinkFree', 33, 558, 155, $green, true);
        $this->drawLines($canvas, $cleanName, 47, 558, 253, 570, 2, $white, true, 58);
        $this->drawLines($canvas, $cleanDescription, 25, 558, 390, 570, 4, $muted, false, 40);
        imagefilledrectangle($canvas, 558, 524, 1117, 527, $stroke);
        $this->drawText($canvas, 'Um link. Todos os seus caminhos.', 21, 558, 562, $muted, false);
        }

        ob_start();
        $ok = imagejpeg($canvas, null, 86);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        if (!$ok || !is_string($binary) || $binary === '') {
            abort(503, 'Could not encode social preview.');
        }
        $headers['Content-Length'] = (string) strlen($binary);
        return response($binary, 200, $headers);
    }

    /**
     * Mobile-first 4:5 poster. The person's photo is the primary content;
     * type is deliberately large and high-contrast when downscaled by WhatsApp.
     * The photo is contained, not cropped.
     */
    private function drawPortrait($canvas, string $name, string $description, ?string $avatarPath): void
    {
        $background = imagecolorallocate($canvas, 9, 15, 11);
        $photoBackground = imagecolorallocate($canvas, 15, 24, 18);
        $panel = imagecolorallocate($canvas, 21, 38, 27);
        $green = imagecolorallocate($canvas, 87, 247, 50);
        $white = imagecolorallocate($canvas, 250, 253, 248);
        $muted = imagecolorallocate($canvas, 219, 229, 219);
        $border = imagecolorallocate($canvas, 52, 83, 60);

        imagefilledrectangle($canvas, 0, 0, self::PROFILE_WIDTH, self::PROFILE_HEIGHT, $background);
        imagefilledrectangle($canvas, 28, 28, 1051, 943, $photoBackground);

        $photo = $this->loadAvatar($avatarPath);
        if ($photo) {
            $photoWidth = imagesx($photo);
            $photoHeight = imagesy($photo);
            $scale = min(1008 / $photoWidth, 899 / $photoHeight);
            $drawWidth = max(1, (int) round($photoWidth * $scale));
            $drawHeight = max(1, (int) round($photoHeight * $scale));

            imagecopyresampled(
                $canvas, $photo,
                (int) (36 + (1008 - $drawWidth) / 2),
                (int) (36 + (899 - $drawHeight) / 2),
                0, 0, $drawWidth, $drawHeight, $photoWidth, $photoHeight
            );
            imagedestroy($photo);
        } else {
            // Accessible text fallback; do not depend on SVG being supported.
            imagefilledellipse($canvas, 540, 475, 490, 490, $green);
            imagefilledellipse($canvas, 540, 475, 458, 458, $photoBackground);
            $this->drawText($canvas, 'LF', 155, 430, 520, $white, true);
            $this->drawText($canvas, 'LinkFree', 90, 290, 725, $white, true);
        }

        imagefilledrectangle($canvas, 28, 955, 1051, 1322, $panel);
        imagefilledrectangle($canvas, 28, 955, 1051, 964, $green);
        imagerectangle($canvas, 28, 955, 1051, 1322, $border);

        $this->drawText($canvas, 'LINKFREE', 29, 76, 1031, $green, true);

        $profileName = $this->cleanText($name, 105);
        $nameLines = $this->wrapForPortrait($profileName, 64, 926, true, 2);
        foreach ($nameLines as $index => $line) {
            $this->drawText($canvas, $line, 64, 76, 1113 + $index * 75, $white, true);
        }

        $descriptionText = $this->cleanText($description, 240);
        $descLines = $this->wrapForPortrait(
            $descriptionText, 35, 926, false, count($nameLines) > 1 ? 2 : 3
        );
        $descriptionTop = count($nameLines) > 1 ? 1245 : 1198;
        foreach ($descLines as $index => $line) {
            $baseline = $descriptionTop + $index * 46;
            if ($baseline > 1303) break;
            $this->drawText($canvas, $line, 35, 76, $baseline, $muted, false);
        }
    }

    /**
     * Wrap using actual TTF metrics. Ellipsize the final line rather than
     * allowing text to escape the image at mobile preview sizes.
     */
    private function wrapForPortrait(string $value, int $size, int $maxWidth, bool $bold, int $limit): array
    {
        $words = preg_split('/\s+/u', trim($value)) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            if ($word === '') continue;
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($line !== '' && $this->width($candidate, $size, $bold) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '') $lines[] = $line;
        if (!$lines) return [];

        $wasTruncated = count($lines) > $limit;
        $lines = array_slice($lines, 0, $limit);
        foreach ($lines as $index => $text) {
            $last = $index === count($lines) - 1;
            $needsEllipsis = $last && $wasTruncated;
            while (mb_strlen($text, 'UTF-8') > 1 &&
                $this->width($text . ($needsEllipsis ? '…' : ''), $size, $bold) > $maxWidth) {
                $text = mb_substr($text, 0, -1, 'UTF-8');
                $needsEllipsis = true;
            }
            $lines[$index] = $text . ($needsEllipsis ? '…' : '');
        }
        return $lines;
    }

    private function loadAvatar(?string $path)
    {
        if (!$path || !is_file($path) || filesize($path) > 10 * 1024 * 1024) {
            return null;
        }

        $details = @getimagesize($path);
        if (!$details || $details[0] < 1 || $details[1] < 1 ||
            $details[0] * $details[1] > 16000000) {
            return null;
        }

        $loaders = [
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
        ];
        $loader = $loaders[$details[2] ?? 0] ?? null;
        return $loader && function_exists($loader) ? @$loader($path) : null;
    }

    private function cleanText(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?: '');
        return mb_strimwidth($value, 0, $limit, '…', 'UTF-8');
    }

    private function font(bool $bold): ?string
    {
        $paths = $bold ? [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
        ] : [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
        ];
        foreach ($paths as $path) {
            if (is_readable($path)) return $path;
        }
        return null;
    }

    private function width(string $text, int $size, bool $bold): int
    {
        $font = $this->font($bold);
        if ($font && function_exists('imagettfbbox')) {
            $bounds = imagettfbbox($size, 0, $font, $text);
            return (int) abs($bounds[2] - $bounds[0]);
        }
        return strlen($text) * 12;
    }

    private function drawText($canvas, string $text, int $size, int $x, int $y, int $color, bool $bold): void
    {
        $font = $this->font($bold);
        if ($font && function_exists('imagettftext')) {
            imagettftext($canvas, $size, 0, $x, $y, $color, $font, $text);
        } else {
            imagestring($canvas, 5, $x, $y - 18, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: '', $color);
        }
    }

    private function drawLines($canvas, string $text, int $size, int $x, int $firstY,
        int $maxWidth, int $maxLines, int $color, bool $bold, int $lineHeight): void
    {
        // $maxWidth is an absolute right boundary.
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($line !== '' && $this->width($candidate, $size, $bold) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '') $lines[] = $line;
        foreach (array_slice($lines, 0, $maxLines) as $index => $value) {
            $this->drawText($canvas, $value, $size, $x, $firstY + $index * $lineHeight, $color, $bold);
        }
    }
}
