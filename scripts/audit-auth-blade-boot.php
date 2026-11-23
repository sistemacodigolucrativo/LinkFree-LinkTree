<?php
declare(strict_types=1);

/**
 * Compila templates que usam componentes Blade após inicializar a ViewFactory
 * real do Laravel, sem abrir conexão ou alterar dados de produção.
 */
$root = dirname(__DIR__).'/linkstack';
chdir($root);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$compiler = app('blade.compiler');

$files = [
    'resources/views/auth/login.blade.php',
    'resources/views/auth/lf-variants.blade.php',
    'resources/views/auth/forgot-password.blade.php',
    'resources/views/auth/register.blade.php',
    'resources/views/auth/reset-password.blade.php',
    'resources/views/auth/confirm-password.blade.php',
    'resources/views/auth/user-confirmation.blade.php',
    'resources/views/auth/verify-email.blade.php',
    'resources/views/auth/blocked.blade.php',
    'resources/views/auth/test.blade.php',
];
$errors = [];
foreach ($files as $file) {
    if (!file_exists($root.'/'.$file)) continue;
    try {
        $php = $compiler->compileString((string) file_get_contents($root.'/'.$file));
        $temp = tempnam(sys_get_temp_dir(), 'lf-auth-');
        if ($temp === false) throw new RuntimeException('Temporary file unavailable');
        try {
            file_put_contents($temp, $php);
            exec('php -l '.escapeshellarg($temp).' 2>&1', $lines, $status);
            if ($status !== 0) $errors[$file] = implode("\n", $lines);
        } finally {
            @unlink($temp);
        }
        $lines = [];
    } catch (\Throwable $error) {
        $errors[$file] = $error->getMessage();
    }
}
foreach ($errors as $name => $reason) fwrite(STDERR, "FAIL {$name}: {$reason}\n");
echo 'Autenticacao — templates compilados: '.count($files).' | erros: '.count($errors)."\n";
exit($errors ? 1 : 0);
