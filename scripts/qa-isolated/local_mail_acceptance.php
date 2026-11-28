<?php
/** Uses only an isolated Laravel fixture and a fake sendmail executable. */
declare(strict_types=1);
$root=realpath((string)getenv('QA_APP_ROOT'));
if (!$root || !str_starts_with($root,'/tmp/linkfree-audit/') || !is_file($root.'/.env')) throw new RuntimeException('Not an isolated fixture');
$env=file_get_contents($root.'/.env');
if (!str_contains($env,'APP_ENV=local') || !str_contains($env,'DB_DATABASE='.$root.'/database/audit.sqlite')) throw new RuntimeException('Unsafe fixture');
chdir($root);
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['mail.mailers.local-php.from_address'=>'sender@example.test','mail.mailers.local-php.domain'=>'example.test',
    'mail.mailers.local-php.from'=>['address'=>'sender@example.test','name'=>'Synthetic'],
    'mail.mailers.local-php.envelope_mode'=>'from','mail.mailers.local-php.hourly_limit'=>2]);
$dir=storage_path('app/linkfree/local-mail');
if (is_file($dir.'/rate.json')) unlink($dir.'/rate.json');
$capture='/tmp/linkfree-audit/local-mail-message.eml';
@unlink($capture);
Illuminate\Support\Facades\Mail::mailer('local-php')->html('<p>Synthetic <strong>message</strong></p>',function($m){
    $m->to('recipient@example.test')->replyTo('reply@example.test')->subject('Verificação segura');
});
if (!is_file($capture)) throw new RuntimeException('Fake local MTA did not receive data');
$raw=file_get_contents($capture);
foreach (['multipart/alternative','text/plain','text/html','recipient@example.test','sender@example.test','reply@example.test','@example.test>','=?utf-8?'] as $expected) {
    if (!str_contains($raw,$expected)) throw new RuntimeException('Missing MIME or address: '.$expected);
}
if (str_contains($raw,"\nBcc:")) throw new RuntimeException('Bcc header leaked');
Illuminate\Support\Facades\Mail::mailer('local-php')->raw('Second message',function($m){$m->to('recipient@example.test')->subject('Second');});
$limited=false;
try { Illuminate\Support\Facades\Mail::mailer('local-php')->raw('Third',function($m){$m->to('recipient@example.test')->subject('Third');}); }
catch (Throwable $e) { $limited=str_contains($e->getMessage(),'hourly limit'); }
if (!$limited) throw new RuntimeException('Hourly limit did not block third send');
echo "PASS: Laravel local PHP mail transport, fake MTA, multipart and hourly limit\n";
