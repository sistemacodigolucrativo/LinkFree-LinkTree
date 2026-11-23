<?php
declare(strict_types=1);

/**
 * Testes de integração em SQLite TEMPORÁRIO; nunca usa .env ou DB da VPS.
 * Abrange migração, consentimento, transação, validação de cadastro e storage.
 */
$base = dirname(__DIR__).'/linkstack';
chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = tempnam(sys_get_temp_dir(), 'lf-consent-db-');
$storage = sys_get_temp_dir().'/lf-storage-'.bin2hex(random_bytes(8));
if ($db === false || !mkdir($storage, 0700)) throw new RuntimeException('Temporary workspace unavailable');
register_shutdown_function(function () use ($db, $storage) {
    @unlink($db);
    $clear = static function (string $path, $clear) {
        if (!is_dir($path)) { @unlink($path); return; }
        foreach (scandir($path) ?: [] as $child) {
            if ($child === '.' || $child === '..') continue;
            $clear($path.'/'.$child, $clear);
        }
        @rmdir($path);
    };
    $clear($storage, $clear);
});
$app->useStoragePath($storage);
config([
    'database.default' => 'sqlite',
    'database.connections.sqlite.database' => $db,
    'cache.default' => 'array',
    'session.driver' => 'array',
    'mail.default' => 'array',
    'linkstack.disable_random_user_ids' => 'true',
]);
\Illuminate\Support\Facades\DB::purge('sqlite');

function verify(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL;
}

$schema = \Illuminate\Support\Facades\Schema::connection('sqlite');
$schema->create('users', function ($table) {
    $table->bigIncrements('id');
    $table->string('name');
    $table->string('email')->unique();
    $table->string('littlelink_name')->unique();
    $table->string('password')->nullable();
    $table->string('role')->default('user');
    $table->string('block')->default('no');
    $table->text('image')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
});
$schema->create('pages', function ($table) {
    $table->bigIncrements('id');
    $table->text('terms');
    $table->text('privacy');
    $table->timestamps();
});
$schema->create('migrations', function ($table) {
    $table->increments('id');
    $table->string('migration');
    $table->integer('batch');
});
$migration = require $base.'/database/migrations/2026_11_23_000001_create_registration_consents_table.php';
$migration->up();
verify($schema->hasTable('registration_consents'), 'Migração cria tabela registration_consents');
foreach (['user_id','method','terms_version','privacy_version','accepted_at'] as $column) {
    verify($schema->hasColumn('registration_consents', $column), 'Coluna de consentimento: '.$column);
}
\Illuminate\Support\Facades\DB::table('pages')->insert([
    'terms' => '<h1>Termos v1</h1>', 'privacy' => '<h1>Privacidade v1</h1>',
]);
$user = \App\Models\User::create([
    'name'=>'Teste sem dados pessoais', 'email'=>'test-'.bin2hex(random_bytes(4)).'@example.test',
    'littlelink_name'=>'test-consent', 'password'=>\Illuminate\Support\Facades\Hash::make('TestPassword!123'),
]);
\App\Services\ConsentReceipt::record($user, 'email');
$receipt = \Illuminate\Support\Facades\DB::table('registration_consents')->where('user_id',$user->id)->first();
verify($receipt !== null && $receipt->method === 'email', 'Consentimento persiste com user_id e modalidade');
verify($receipt->terms_version === hash('sha256','<h1>Termos v1</h1>'), 'Versão corresponde ao hash dos Termos publicados');
verify($receipt->privacy_version === hash('sha256','<h1>Privacidade v1</h1>'), 'Versão corresponde ao hash da Privacidade publicada');
verify(!empty($receipt->accepted_at), 'Data do aceite está registrada');
try {
    \Illuminate\Support\Facades\DB::transaction(function () {
        $other = \App\Models\User::create([
            'name'=>'Rolled back', 'email'=>'rollback@example.test', 'littlelink_name'=>'rollback-consent',
        ]);
        \App\Services\ConsentReceipt::record($other,'social');
        throw new RuntimeException('Expected transaction rollback');
    });
} catch (RuntimeException $error) {
    verify($error->getMessage() === 'Expected transaction rollback','Falha simulada inicia rollback');
}
verify(\App\Models\User::where('littlelink_name','rollback-consent')->doesntExist(), 'Transação reverte cadastro quando há falha');
verify(\Illuminate\Support\Facades\DB::table('registration_consents')->count() === 1, 'Transação reverte recibo do cadastro');
try {
    $input = \Illuminate\Http\Request::create('/register', 'POST', [
        'name'=>'Sem aceite', 'email'=>'no-consent@example.test',
        'littlelink_name'=>'sem-aceite', 'password'=>'TestPassword!123',
    ]);
    app()->instance('request',$input);
    (new \App\Http\Controllers\Auth\RegisteredUserController())->store($input);
    throw new RuntimeException('Validation failed to reject missing consent');
} catch (\Illuminate\Validation\ValidationException $exception) {
    verify(isset($exception->errors()['accept_terms']), 'Cadastro rejeita requisição sem aceite no servidor');
}
verify(\App\Models\User::where('email','no-consent@example.test')->doesntExist(), 'Nenhum usuário criado sem aceite');
\App\Services\ThemeOptions::savePermissions(['font_family'=>'1','spacing'=>'1']);
verify(\App\Services\ThemeOptions::permissions()['font_family'] === true, 'Permissão de personalização persistida em JSON');
verify(\App\Services\ThemeOptions::permissions()['button_color'] === false, 'Controles não liberados permanecem inativos');
\App\Services\ThemeOptions::savePermissions(['spacing'=>'1']);
verify(\App\Services\ThemeOptions::permissions()['font_family'] === false, 'Alterações de permissão são persistentes');
$request = \Illuminate\Http\Request::create('/@tester?utm_source=instagram', 'GET');
\App\Services\VisitAttribution::record((int)$user->id, $request);
$counts = \App\Services\VisitAttribution::summary((int)$user->id, 30);
verify($counts['instagram']===1,'Relatório de origem persiste e volta a ser lido');
$raw=file_get_contents($storage.'/app/linkfree/visit-sources/'.$user->id.'.json');
verify(strpos($raw,'test-consent')===false && strpos($raw,'@example.test')===false, 'Agregados não armazenam identificadores pessoais');
echo "CONSENT AND STORAGE SQLITE INTEGRATION PASS\n";
