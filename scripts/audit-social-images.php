<?php
/**
 * Exercise actual GD rendering without booting Laravel or touching the database.
 * GitHub Actions provides PHP 8.3 with GD and mbstring.
 */
namespace App\Http\Controllers {
    class Controller {}
}

namespace Illuminate\Http {
    class Request {
        public function header($name) { return null; }
    }
}

namespace {
    function abort($status, $message = '') {
        throw new \RuntimeException('Unexpected abort HTTP ' . $status . ': ' . $message);
    }

    function response($body = '', $status = 200, $headers = []) {
        return ['body' => $body, 'status' => $status, 'headers' => $headers];
    }

    require dirname(__DIR__) . '/linkstack/app/Http/Controllers/SocialPreviewController.php';

    $renderer = new \App\Http\Controllers\SocialPreviewController();
    $render = new \ReflectionMethod($renderer, 'image');
    $request = new \Illuminate\Http\Request();

    foreach ([
        ['portrait', true, 1080, 1350],
        ['landscape', false, 1200, 630],
    ] as [$label, $portrait, $expectedWidth, $expectedHeight]) {
        $result = $render->invoke(
            $renderer,
            $request,
            'Perfil de demonstração',
            'Encontre todos os links e canais de contato em um só lugar.',
            null,
            $portrait
        );

        if ($result['status'] !== 200 ||
            $result['headers']['Content-Type'] !== 'image/jpeg' ||
            strlen($result['body']) < 1000) {
            throw new \RuntimeException($label . ' did not return a real JPEG.');
        }

        $dimensions = getimagesizefromstring($result['body']);
        if (!$dimensions || $dimensions[0] !== $expectedWidth ||
            $dimensions[1] !== $expectedHeight ||
            ($dimensions['mime'] ?? '') !== 'image/jpeg') {
            throw new \RuntimeException(
                $label . ' has incorrect dimensions/mime: ' . json_encode($dimensions)
            );
        }

        $image = imagecreatefromstring($result['body']);
        if (!$image) throw new \RuntimeException($label . ' could not be decoded by GD.');
        imagedestroy($image);

        echo "PASS {$label}: {$expectedWidth}x{$expectedHeight} real JPEG (" .
            strlen($result['body']) . " bytes)\n";
    }
}
