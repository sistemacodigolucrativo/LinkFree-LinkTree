<?php
namespace App\Services;

use Illuminate\Http\Request;

final class VisitAttribution
{
    /** Armazena apenas contagens diarias agregadas, sem IP ou URL completa. */
    public static function source(Request $request): string
    {
        $utm = strtolower(preg_replace('/[^a-z0-9_-]/', '', substr((string) $request->query('utm_source', ''), 0, 40)));
        if ($utm !== '') return match (true) {
            str_contains($utm, 'instagram') => 'instagram',
            str_contains($utm, 'whatsapp') => 'whatsapp',
            str_contains($utm, 'telegram') => 'telegram',
            default => 'campanha',
        };
        $host = strtolower((string) parse_url((string) $request->headers->get('referer', ''), PHP_URL_HOST));
        if ($host === '' || $host === strtolower($request->getHost())) return 'direto';
        if (preg_match('/(^|\.)instagram\.com$/', $host)) return 'instagram';
        if (preg_match('/(^|\.)(whatsapp\.com|wa\.me)$/', $host)) return 'whatsapp';
        if (preg_match('/(^|\.)(t\.me|telegram\.(org|me))$/', $host)) return 'telegram';
        return 'outros';
    }

    public static function record(int $userId, Request $request): void
    {
        $dir = storage_path('app/linkfree/visit-sources');
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) return;
        $path = $dir.'/'.$userId.'.json';
        $lock = fopen($path.'.lock', 'c');
        if ($lock === false) return;
        try {
            if (!flock($lock, LOCK_EX)) return;
            $data = [];
            if (is_file($path)) {
                $saved = json_decode((string) file_get_contents($path), true);
                if (is_array($saved)) $data = $saved;
            }
            $cutoff = now()->subDays(90)->toDateString();
            $data = array_filter($data, fn($date) => $date >= $cutoff, ARRAY_FILTER_USE_KEY);
            $date = now()->toDateString();
            $source = self::source($request);
            if (!isset($data[$date]) || !is_array($data[$date])) $data[$date] = [];
            $data[$date][$source] = (int) ($data[$date][$source] ?? 0) + 1;
            if ($source === 'campanha') {
                $campaign = preg_replace('/[^a-zA-Z0-9_.-]/', '', substr((string) $request->query('utm_campaign', ''), 0, 50));
                if ($campaign !== '') {
                    $key = 'utm:'.$campaign;
                    $data[$date][$key] = (int) ($data[$date][$key] ?? 0) + 1;
                }
            }
            $tmp = tempnam($dir, 'tmp-');
            if ($tmp !== false) {
                try {
                    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    if (file_put_contents($tmp, $json, LOCK_EX) !== false) {
                        chmod($tmp, 0640);
                        rename($tmp, $path);
                    }
                } finally {
                    if (is_file($tmp)) unlink($tmp);
                }
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function summary(int $userId, int $days = 30): array
    {
        $path = storage_path('app/linkfree/visit-sources/'.$userId.'.json');
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $result = ['direto' => 0, 'instagram' => 0, 'whatsapp' => 0, 'telegram' => 0, 'campanha' => 0, 'outros' => 0];
        if (!is_array($data)) return $result;
        $from = now()->subDays($days - 1)->toDateString();
        foreach ($data as $date => $sources) {
            if ($date < $from || !is_array($sources)) continue;
            foreach ($sources as $source => $count) {
                if (!isset($result[$source]) && !str_starts_with((string) $source, 'utm:')) continue;
                $result[$source] = (int) ($result[$source] ?? 0) + (int) $count;
            }
        }
        return $result;
    }
}
