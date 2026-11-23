<?php
namespace App\Services;

use App\Models\UserData;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class ThemeOptions
{
    private const FILE = 'linkfree/theme-options.json';

    public static function fields(): array
    {
        return [
            'background_color' => ['label' => 'lf_theme_background', 'kind' => 'color'],
            'text_color' => ['label' => 'lf_theme_text', 'kind' => 'color'],
            'button_color' => ['label' => 'lf_theme_button_color', 'kind' => 'color'],
            'button_text' => ['label' => 'lf_theme_button_text', 'kind' => 'color'],
            'font_family' => ['label' => 'lf_theme_font', 'kind' => 'select', 'choices' => ['system','serif','mono']],
            'button_style' => ['label' => 'lf_theme_button_style', 'kind' => 'select', 'choices' => ['solid','outline','pill']],
            'border_radius' => ['label' => 'lf_theme_radius', 'kind' => 'select', 'choices' => ['small','medium','large']],
            'spacing' => ['label' => 'lf_theme_spacing', 'kind' => 'select', 'choices' => ['compact','normal','relaxed']],
        ];
    }

    public static function permissions(): array
    {
        $path = storage_path('app/'.self::FILE);
        $saved = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        if (!is_array($saved)) $saved = [];
        $allowed = [];
        foreach (self::fields() as $key => $definition) {
            $allowed[$key] = ($saved[$key] ?? false) === true;
        }
        return $allowed;
    }

    public static function allowed(): array
    {
        return array_filter(self::permissions(), fn($v) => $v === true);
    }

    public static function savePermissions(array $input): void
    {
        $values = [];
        foreach (array_keys(self::fields()) as $key) {
            $values[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        $dir = storage_path('app/linkfree');
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Pasta de configuracao indisponivel');
        }
        $path = storage_path('app/'.self::FILE);
        $lock = fopen($path.'.lock', 'c');
        if (!$lock) throw new RuntimeException('Configuracao bloqueada');
        try {
            if (!flock($lock, LOCK_EX)) throw new RuntimeException('Sem lock');
            $tmp = tempnam($dir, 'theme-');
            if ($tmp === false) throw new RuntimeException('Falha no arquivo temporario');
            try {
                $data = json_encode($values, JSON_THROW_ON_ERROR);
                if (file_put_contents($tmp, $data, LOCK_EX) === false || !rename($tmp, $path)) {
                    throw new RuntimeException('Falha ao salvar preferencias');
                }
            } finally {
                if (is_file($tmp)) unlink($tmp);
            }
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private static function channel(float $x): float
    {
        $x /= 255;
        return $x <= .04045 ? $x / 12.92 : (($x + .055) / 1.055) ** 2.4;
    }

    public static function contrast(string $a, string $b): float
    {
        $rgb = function (string $hex): float {
            $v = array_map(fn($i) => self::channel((float) hexdec(substr($hex, $i, 2))), [1,3,5]);
            return .2126*$v[0] + .7152*$v[1] + .0722*$v[2];
        };
        $l1 = $rgb($a); $l2 = $rgb($b);
        return (max($l1,$l2)+.05)/(min($l1,$l2)+.05);
    }

    public static function validated(array $input, array $allowed): array
    {
        $output = [];
        foreach (self::fields() as $key => $field) {
            if (!isset($input[$key]) || $input[$key] === '') continue;
            if (!isset($allowed[$key])) throw ValidationException::withMessages([
                $key => __('messages.lf_theme_unavailable'),
            ]);
            $value = (string) $input[$key];
            if ($field['kind'] === 'color') {
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/D', $value)) {
                    throw ValidationException::withMessages([$key => __('messages.lf_theme_invalid')]);
                }
                $output[$key] = strtolower($value);
            } else {
                if (!in_array($value, $field['choices'], true)) {
                    throw ValidationException::withMessages([$key => __('messages.lf_theme_invalid')]);
                }
                $output[$key] = $value;
            }
        }
        if (isset($output['background_color'], $output['text_color']) &&
            self::contrast($output['background_color'], $output['text_color']) < 4.5) {
            throw ValidationException::withMessages([
                'text_color' => __('messages.lf_theme_contrast'),
            ]);
        }
        if (isset($output['button_color'], $output['button_text']) &&
            self::contrast($output['button_color'], $output['button_text']) < 4.5) {
            throw ValidationException::withMessages([
                'button_text' => __('messages.lf_theme_contrast'),
            ]);
        }
        return $output;
    }

    public static function userValues(int $userId): array
    {
        $stored = UserData::getData($userId, 'theme_options');
        if (!is_array($stored)) return [];
        $allowed = self::allowed();
        try {
            return self::validated(array_intersect_key($stored, $allowed), $allowed);
        } catch (ValidationException $exception) {
            return [];
        }
    }

    public static function saveForUser(int $userId, array $input): void
    {
        $allowed = self::allowed();
        $valid = self::validated($input, $allowed);
        $previous = UserData::getData($userId, 'theme_options');
        $previous = is_array($previous) ? $previous : [];
        $next = array_replace($previous, $valid);
        // Revalida cores combinadas previamente salvas, nao apenas os campos enviados.
        self::validated(array_intersect_key($next, $allowed), $allowed);
        UserData::saveData($userId, 'theme_options', $next);
    }

    /** Gera CSS apenas a partir de cores hexadecimais e enums de uma lista fechada. */
    public static function css(int $userId): string
    {
        $v = self::userValues($userId);
        $css = [];
        $font = ['system' => 'system-ui,sans-serif', 'serif' => 'Georgia,serif', 'mono' => 'ui-monospace,monospace'];
        $radius = ['small' => '6px', 'medium' => '14px', 'large' => '24px'];
        $spacing = ['compact' => '7px', 'normal' => '12px', 'relaxed' => '20px'];
        if (isset($v['background_color'])) $css[] = 'body{background-color:'.$v['background_color'].'!important}';
        if (isset($v['text_color'])) $css[] = 'body,.container{color:'.$v['text_color'].'!important}';
        if (isset($font[$v['font_family'] ?? ''])) $css[] = 'body{font-family:'.$font[$v['font_family']].'!important}';
        if (isset($v['button_color'])) $css[] = '.button{background-color:'.$v['button_color'].'!important}';
        if (isset($v['button_text'])) $css[] = '.button{color:'.$v['button_text'].'!important}';
        if (isset($radius[$v['border_radius'] ?? ''])) $css[] = '.button{border-radius:'.$radius[$v['border_radius']].'!important}';
        if (isset($spacing[$v['spacing'] ?? ''])) $css[] = '.button{margin-block:'.$spacing[$v['spacing']].'!important}';
        if (($v['button_style'] ?? '') === 'pill') $css[] = '.button{border-radius:999px!important}';
        if (($v['button_style'] ?? '') === 'outline') $css[] = '.button{background-color:transparent!important;border:2px solid currentColor!important}';
        return implode("\n", $css);
    }
}
