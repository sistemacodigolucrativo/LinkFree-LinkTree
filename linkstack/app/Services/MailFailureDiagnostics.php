<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

/** Record a fixed category and exception type, never an exception message or mail data. */
class MailFailureDiagnostics
{
    public static function record(string $operation, Throwable $exception): void
    {
        $text = '';
        for ($cause = $exception, $depth = 0; $cause && $depth < 5; $cause = $cause->getPrevious(), $depth++) {
            $text .= ' '.strtolower($cause->getMessage());
        }
        $reason = 'other';
        if (preg_match('/getaddrinfo|name or service not known|temporary failure in name resolution|could not resolve host/', $text)) {
            $reason = 'dns';
        } elseif (preg_match('/failed to authenticate|authentication failed|invalid credentials/', $text)) {
            $reason = 'authentication';
        } elseif (preg_match('/certificate verify failed|ssl operation failed|tls handshake/', $text)) {
            $reason = 'tls';
        } elseif (preg_match('/connection refused|connection timed out|operation timed out/', $text)) {
            $reason = 'connection';
        }
        Log::warning('Email delivery failed', [
            'operation' => $operation,
            'exception_type' => get_class($exception),
            'reason' => $reason,
        ]);
    }
}
