<?php
// Pure renderer tests; no application boot, database, network or user files.
declare(strict_types=1);
require __DIR__.'/../../linkstack/vendor/autoload.php';
require_once __DIR__.'/../../linkstack/app/Http/Controllers/SocialPreviewController.php';
$r = new App\Http\Controllers\SocialPreviewController();
function invoke($r, string $name, ...$args) {
    return (new ReflectionMethod($r, $name))->invoke($r, ...$args);
}
function verify(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException($label);
    echo "PASS: $label\n";
}
verify(extension_loaded('gd') && function_exists('imagettfbbox') && invoke($r, 'font', true) !== null, 'real GD and FreeType font');
$raw = "💻 Desenvolvedor Full Stack 🧠 Tecnologia • Inteligência • Estratégia.🚀";
$clean = "Desenvolvedor Full Stack Tecnologia • Inteligência • Estratégia.";
verify(invoke($r, 'cleanText', $raw, 240) === $clean, 'emoji-only removal, preserving accents and bullet');
verify(invoke($r, 'cleanText', "👩🏽‍💻 Família 👨‍👩‍👧‍👦 🇧🇷 1️⃣ ação © ® ™", 240) === "Família ação © ® ™", 'ZWJ, modifiers, flags and keycaps removed as complete clusters; text marks retained');
verify(invoke($r, 'cleanText', "Cafe\u{0301}", 90) === "Cafe\u{0301}", 'combining accents remain intact');
foreach (['Ana', 'Roberto R. Almeida', 'José Antônio da Conceição', str_repeat('Nome Muito Longo ', 10), str_repeat('W', 90)] as $name) {
    [$fitted, $size] = invoke($r, 'fitName', $name, 550);
    verify($size >= 26 && $size <= 47 && invoke($r, 'width', $fitted, $size, true) <= 550 && !str_contains($fitted, "\n"), 'single title line within 550 px');
    if ($name === 'Roberto R. Almeida') verify($name === $fitted && $size < 47, 'medium title remains complete with adaptive font');
    if (strlen($name) > 80) verify(str_ends_with($fitted, '…'), 'long title uses ellipsis at readable minimum');
}
$lines = invoke($r, 'wrapText', str_repeat('Descrição longa e acentuada ', 25), 25, 550, false, 5);
verify(count($lines) === 5 && str_ends_with(end($lines), '…'), 'description uses at most five lines with final ellipsis');
foreach ($lines as $line) verify(invoke($r, 'width', $line, 25, false) <= 550, 'description line fits');
$lines = invoke($r, 'wrapText', str_repeat('W', 150), 25, 550, false, 5);
verify(count($lines) === 1 && invoke($r, 'width', $lines[0], 25, false) <= 550 && str_ends_with($lines[0], '…'), 'unbroken word bounded');
