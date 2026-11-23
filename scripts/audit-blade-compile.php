<?php
/**
 * Compila e verifica a sintaxe dos templates Blade sem tocar no banco de dados.
 * Evita regressões de HTTP 500 não detectadas por php -l nos controladores.
 */
declare(strict_types=1);

$base = dirname(__DIR__).'/linkstack';
$autoload = $base.'/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Composer vendor/autoload.php não encontrado.\n");
    exit(2);
}
require $autoload;
$blade = new \Illuminate\View\Compilers\BladeCompiler(new \Illuminate\Filesystem\Filesystem(), sys_get_temp_dir());
$root = $base.'/resources/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$checked = 0;
$errors = [];
foreach ($iterator as $item) {
    if (!$item->isFile() || !str_ends_with($item->getFilename(), '.blade.php')) continue;
    $filename = $item->getPathname();
    if (str_contains(str_replace('\\', '/', $filename), '/resources/views/vendor/')) continue;
    try {
        $source = file_get_contents($filename);
        $compiled = $blade->compileString((string) $source);
        $tmp = tempnam(sys_get_temp_dir(), 'blade-audit-');
        if ($tmp === false) throw new RuntimeException('Arquivo temporário indisponível');
        file_put_contents($tmp, $compiled);
        $output = [];
        $status = 0;
        exec('php -l '.escapeshellarg($tmp).' 2>&1', $output, $status);
        unlink($tmp);
        $checked++;
        if ($status !== 0) {
            $errors[] = ['path' => substr($filename, strlen($base) + 1), 'message' => implode("\n", $output)];
        }
    } catch (Throwable $e) {
        $errors[] = ['path' => substr($filename, strlen($base) + 1), 'message' => $e->getMessage()];
    }
}
foreach ($errors as $error) {
    fwrite(STDERR, "FAIL ".$error['path']."\n".$error['message']."\n");
}
echo "Blade compilados e verificados: ".$checked." | erros: ".count($errors)."\n";
exit(count($errors) === 0 ? 0 : 1);
