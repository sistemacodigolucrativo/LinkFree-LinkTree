<?php
// ONLY synthetic data, ONLY in the ephemeral /tmp/linkfree-audit checkout.
declare(strict_types=1);
$root = realpath((string) getenv('QA_APP_ROOT'));
if (!$root || !str_starts_with($root, '/tmp/linkfree-audit/') || !is_file($root.'/.env')) {
    throw new RuntimeException('Refusing to seed a non-isolated application.');
}
$env = file_get_contents($root.'/.env');
$expectedDb = $root.'/database/audit.sqlite';
if (!str_contains($env, 'APP_ENV=local') || !str_contains($env, 'DB_DATABASE='.$expectedDb) || !str_contains($env, 'MAIL_MAILER=log')) {
    throw new RuntimeException('Isolated env check failed.');
}
chdir($root);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' ||
    config('database.connections.sqlite.database') !== $expectedDb) {
    throw new RuntimeException('Database target is NOT the disposable synthetic database.');
}
$db = Illuminate\Support\Facades\DB::connection();
if ($db->table('users')->count() !== 0) {
    throw new RuntimeException('Database is not empty; refusing to replace fixtures.');
}
$now = date('Y-m-d H:i:s');
$db->table('buttons')->insert(['id'=>1, 'name'=>'Synthetic button', 'created_at'=>$now, 'updated_at'=>$now]);
foreach (['amazon'=>'Amazon', 'apple-books'=>'Apple Books', 'appstore'=>'Apple Store',
          'deezer'=>'Deezer', 'discord'=>'Discord', 'whatsapp'=>'WhatsApp'] as $name=>$alt) {
    $db->table('buttons')->insert([
        'name'=>$name, 'alt'=>$alt, 'exclude'=>false, 'created_at'=>$now, 'updated_at'=>$now
    ]);
}
foreach ([
    [1, 'auditadmin', 'admin'],
    [2, 'auditfree', 'user'],
    [3, 'auditother', 'user'],
    [4, 'auditvip', 'vip']
] as [$id, $name, $role]) {
    $db->table('users')->insert([
        'id'=>$id, 'name'=>$name, 'email'=>$name.'@example.test',
        'password'=>Illuminate\Support\Facades\Hash::make('Synthetic-password-123'),
        'littlelink_name'=>$name, 'email_verified_at'=>$now,
        'block'=>'no', 'role'=>$role, 'created_at'=>$now, 'updated_at'=>$now
    ]);
}
foreach ([[101,2], [102,3], [103,4], [104,2], [105,3]] as [$id,$uid]) {
    $db->table('links')->insert([
        'id'=>$id, 'user_id'=>$uid, 'button_id'=>1,
        'link'=>'https://example.test/qa-'.$id, 'title'=>'QA synthetic '.$id,
        'type'=>'link', 'created_at'=>$now, 'updated_at'=>$now
    ]);
}
$db->table('pages')->insert([
    'terms'=>'Synthetic QA terms', 'privacy'=>'Synthetic QA privacy',
    'contact'=>'Synthetic QA contact', 'created_at'=>$now, 'updated_at'=>$now
]);
if ($db->table('users')->count() !== 4 || $db->table('links')->count() !== 5) {
    throw new RuntimeException('Synthetic seed verification failed.');
}
echo "PASS: synthetic SQLite and fixtures initialized without production data.\n";
