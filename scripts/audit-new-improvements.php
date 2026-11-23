<?php
declare(strict_types=1);

require dirname(__DIR__).'/linkstack/vendor/autoload.php';

use App\Services\WhatsAppLink;
use App\Services\ThemeOptions;
use App\Services\UserLinkLimit;
use App\Services\VisitAttribution;
use Illuminate\Http\Request;

function check(bool $condition, string $message): void
{
    if (!$condition) {fwrite(STDERR, "FAIL: ".$message."\n"); exit(1);}
    echo "PASS: ".$message."\n";
}

$url=WhatsAppLink::build('55', '(31) 99999-8888', 'Olá LinkFree!');
check($url === 'https://wa.me/5531999998888?text=Ol%C3%A1%20LinkFree%21', 'WhatsApp canonical URL with encoded message');
check(ThemeOptions::contrast('#ffffff', '#000000') > 20, 'WCAG contrast calculation for accessible colors');
check(ThemeOptions::contrast('#777777', '#777777') < 4.5, 'WCAG contrast detects insufficient contrast');
check(UserLinkLimit::isCounted((object)['type'=>'link']), 'Regular link counts toward free quota');
check(!UserLinkLimit::isCounted((object)['type'=>'heading']), 'Decorative heading does not count');
check(UserLinkLimit::countImportable([['type'=>'link'],['type'=>'spacer']]) === 1, 'Import preflight checks link quota');
$utm=Request::create('/profile?utm_source=instagram', 'GET');
check(VisitAttribution::source($utm) === 'instagram', 'UTM Instagram attribution');
$direct=Request::create('/profile', 'GET');
check(VisitAttribution::source($direct) === 'direto', 'Direct access without referrer');
$telegram=Request::create('/profile', 'GET', [], [], [], ['HTTP_REFERER'=>'https://t.me/exemplo']);
check(VisitAttribution::source($telegram) === 'telegram', 'Telegram referrer attribution');
echo "ENHANCEMENT PHP REGRESSION PASS\n";
