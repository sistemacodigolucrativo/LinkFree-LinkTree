<?php
namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class ThemeInstaller
{
    public function install(string $archive, ?string $expected = null): string
    {
        $zip = new ZipArchive;
        if ($zip->open($archive, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('ZIP inválido.');
        $stage = storage_path('app/theme-stage-'.bin2hex(random_bytes(12)));
        $root = null;
        $total = 0;
        try {
            if ($zip->numFiles > 5000) throw new RuntimeException('Pacote excede o limite de arquivos.');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $s = $zip->statIndex($i);
                $name = $s['name'];
                $parts = explode('/', rtrim($name, '/'));
                $zip->getExternalAttributesIndex($i, $opsys, $attrs);
                if (!$name || str_contains($name, '\\') || str_contains($name, ':') || str_contains($name, "\0") || in_array('..', $parts, true) || in_array('.', $parts, true) || in_array('', $parts, true) || (($attrs >> 16) & 0170000) === 0120000) throw new RuntimeException('Caminho inseguro no ZIP.');
                $root = $root ?? $parts[0];
                if ($parts[0] !== $root || !preg_match('/^[A-Za-z][A-Za-z0-9_-]*(?:\.[0-9]+)*$/D', $root)) throw new RuntimeException('O ZIP deve conter uma única pasta de tema.');
                $total += $s['size'];
                if ($total > 100 * 1024 * 1024) throw new RuntimeException('Tema descompactado excede 100 MB.');
            }
            if (!$root || $zip->locateName($root.'/readme.md', ZipArchive::FL_NOCASE) === false) throw new RuntimeException('readme.md do tema não encontrado.');
            $targetName = $expected ?? preg_replace('/[0-9.-]/', '', $root);
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $targetName)) throw new RuntimeException('Nome de tema inválido.');
            File::makeDirectory($stage, 0700, true);
            if (!$zip->extractTo($stage)) throw new RuntimeException('Falha na extração.');
            $target = base_path('themes/'.$targetName);
            if (is_link($target)) throw new RuntimeException('Destino inválido.');
            $backup = $stage.'/previous';
            if (is_dir($target) && !rename($target, $backup)) throw new RuntimeException('Não foi possível preservar o tema anterior.');
            if (!rename($stage.'/'.$root, $target)) {
                if (is_dir($backup)) rename($backup, $target);
                throw new RuntimeException('Falha ao instalar tema.');
            }
            return $targetName;
        } finally {
            $zip->close();
            File::deleteDirectory($stage);
        }
    }
}
