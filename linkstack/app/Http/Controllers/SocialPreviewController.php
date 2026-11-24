<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PublicSeo;
use Illuminate\Http\Request;

/**
 * Real JPEG previews for social crawlers. Does not fetch remote URLs or expose
 * private profile data. An avatar is read only from the site's own avatar folder.
 */
class SocialPreviewController extends Controller
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    public function site(Request $request)
    {
        return $this->image($request, config('app.name', 'LinkFree'),
            PublicSeo::homeDescription(), PublicSeo::avatar());
    }

    public function profile(Request $request, int $id)
    {
        $user = User::query()->select('id', 'name', 'littlelink_description', 'littlelink_name', 'block')->findOrFail($id);
        abort_if($user->block === 'yes' || empty($user->littlelink_name), 404);
        return $this->image($request, PublicSeo::plain($user->name) ?: $user->littlelink_name,
            PublicSeo::description($user->littlelink_description, 'Confira meus links e canais de contato', 240),
            PublicSeo::avatar($id));
    }

    public function profileWide(Request $request, int $id)
    {
        return $this->profile($request, $id);
    }

    private function image(Request $request, string $name, string $description, ?string $avatarPath)
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            abort(503, 'GD image extension is required for social previews.');
        }

        $version = $avatarPath ? (@hash_file('sha256', $avatarPath) ?: 'brand') : 'brand';
        // Changing the format changes the ETag, even if no user data changed.
        $fingerprint = hash('sha256', PublicSeo::CARD_VERSION
            . '|' . $name . '|' . $description . '|' . $version);
        $etag = '"' . substr($fingerprint, 0, 32) . '"';
        $headers = [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, no-cache, must-revalidate',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if (!$canvas) {
            abort(503, 'Unable to allocate social preview canvas.');
        }

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
                (int) (60 + (458 - $drawWidth) / 2), (int) (59 + (512 - $drawHeight) / 2),
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
        [$fittedName, $nameSize] = $this->fitName($cleanName, 550);
        $this->drawText($canvas, $fittedName, $nameSize, 558, 253, $white, true);
        $this->drawLines($canvas, $cleanDescription !== '' ? $cleanDescription : 'Confira meus links e canais de contato', 25, 558, 350, 550, 5, $muted, false, 36);
        imagefilledrectangle($canvas, 558, 524, 1117, 527, $stroke);
        $this->drawText($canvas, 'Um link. Todos os seus caminhos.', 21, 558, 562, $muted, false);
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

    /** A single line, with a readable minimum size and grapheme-safe ellipsis. */
    private function fitName(string $name, int $maxWidth): array
    {
        $name = $name !== '' ? $name : 'LinkFree';
        for ($size = 47; $size >= 26; $size--) {
            if ($this->width($name, $size, true) <= $maxWidth) return [$name, $size];
        }
        return [$this->ellipsize($name, 26, $maxWidth, true), 26];
    }

    private function ellipsize(string $text, int $size, int $maxWidth, bool $bold, bool $force = false): string
    {
        if (!$force && $this->width($text, $size, $bold) <= $maxWidth) return $text;
        preg_match_all('/\X/u', $text, $matches);
        $clusters = $matches[0];
        while ($clusters) {
            $candidate = rtrim(implode('', $clusters)) . '…';
            if ($this->width($candidate, $size, $bold) <= $maxWidth) return $candidate;
            array_pop($clusters);
        }
        return $this->width('…', $size, $bold) <= $maxWidth ? '…' : '';
    }

    /** Wrap on words, preserving a bounded final line with a single ellipsis. */
    private function wrapText(string $value, int $size, int $maxWidth, bool $bold, int $limit): array
    {
        $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
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
        $truncated = count($lines) > $limit;
        $lines = array_slice($lines, 0, $limit);
        foreach ($lines as $index => $text) {
            $lines[$index] = $this->ellipsize($text, $size, $maxWidth, $bold,
                $truncated && $index === count($lines) - 1);
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
        // GD/FreeType's ordinary text fonts cannot reliably render emoji sequences.
        // Omit the complete emoji grapheme ONLY in the raster card. Do not rewrite
        // the member's saved bio or the HTML/Open Graph/Twitter metadata.
        $value = mb_convert_encoding(strip_tags($value), 'UTF-8', 'UTF-8');
        $value = preg_replace_callback('/\X/u', static function (array $match): string {
            $cluster = $match[0];
            if (preg_match('/[\p{Emoji_Presentation}\p{Regional_Indicator}\x{20E3}\x{FE0F}\x{1F3FB}-\x{1F3FF}]/u', $cluster)
                || (preg_match('/\p{Extended_Pictographic}/u', $cluster)
                    && !preg_match('/^[©®™]\x{FE0E}?$/u', $cluster))) {
                return ' ';
            }
            return $cluster;
        }, $value) ?? '';
        $value = preg_replace('/[\p{Cc}\p{Cf}\x{FE0E}\x{FE0F}]+/u', ' ', $value) ?? '';
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        // Avoid splitting a combining sequence at the character limit.
        preg_match_all('/\X/u', $value, $parts);
        return count($parts[0]) > $limit ? implode('', array_slice($parts[0], 0, $limit - 1)) . '…' : $value;
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
            if (is_array($bounds)) return (int) (max($bounds[0], $bounds[2], $bounds[4], $bounds[6]) - min($bounds[0], $bounds[2], $bounds[4], $bounds[6]));
        }
        return strlen($text) * imagefontwidth(5);
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
        // Bound long names, long words and ellipsis by actual pixel width.
        foreach ($this->wrapText($text, $size, $maxWidth, $bold, $maxLines) as $index => $value) {
            $this->drawText($canvas, $value, $size, $x, $firstY + $index * $lineHeight, $color, $bold);
        }
    }
}
