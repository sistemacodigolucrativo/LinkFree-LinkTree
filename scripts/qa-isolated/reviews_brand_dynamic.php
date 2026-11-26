<?php
// Evaluate runtime brand changes only inside the disposable QA fixture.
$root = '/tmp/linkfree-audit/linkstack';
if (getenv('QA_EXPECTED_SITE') !== 'http://127.0.0.1:18765'
    || !is_file($root.'/.env')
    || !str_contains(file_get_contents($root.'/.env'), 'APP_ENV=local')) {
    throw new RuntimeException('Non-isolated QA refused');
}
chdir($root);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['app.name' => 'Minha Plataforma']);
foreach (['ar','bg','de','el','en','es','fr','gl','pt-BR','ru','sv','vi','zh-TW','zh'] as $locale) {
    app()->setLocale($locale);
    foreach (['messages.LinkStack setup', 'messages.Setup LinkStack',
              'messages.Welcome to the setup for LinkStack!',
              'messages.Update LinkStack', 'messages.SMTP.description'] as $key) {
        $value = __($key);
        if (!str_contains($value, 'Minha Plataforma') || str_contains($value, 'LinkStack')) {
            throw new RuntimeException("$locale: $key is not using configured app.name");
        }
    }
}
app()->setLocale('pt-BR');
if (!str_contains(__('reviews.intro', ['appName' => config('app.name')]), 'Minha Plataforma')) {
    throw new RuntimeException('Review copy is not dynamic');
}
if (!str_contains(App\Services\PublicSiteContent::text('faq_title'), 'Minha Plataforma')) {
    throw new RuntimeException('Default homepage CMS copy is not dynamic');
}
if (str_contains(App\Services\MemberFeatureCatalog::groups()[0]['items'][0]['title'], 'LinkFree')) {
    throw new RuntimeException('Member catalog still has a fixed brand');
}
echo "PASS: 14 translation catalogues, public copy, review copy use APP_NAME without modifying persistent data.\n";
