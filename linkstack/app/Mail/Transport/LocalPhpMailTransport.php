<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/** Local PHP mail transport. A successful call means local acceptance only. */
final class LocalPhpMailTransport extends AbstractTransport
{
    private array $options;

    public function __construct(array $options)
    {
        parent::__construct();
        $this->options = $options;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if (!$message instanceof Email) {
            throw new TransportException('The local mail transport requires a MIME email.');
        }
        $message = clone $message;
        $domain = strtolower((string) ($this->options['domain'] ?? ''));
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new TransportException('Invalid local mail domain.');
        }
        if (!$message->getHeaders()->has('Message-ID')) {
            $message->getHeaders()->addIdHeader('Message-ID', bin2hex(random_bytes(16)).'@'.$domain);
        }
        $html = $message->getHtmlBody();
        $plain = $message->getTextBody();
        if (is_string($html) && $plain === null) {
            $text = trim(html_entity_decode(strip_tags(preg_replace('/<\\s*br\\s*\\/?\\s*>/i', "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $message->text($text === '' ? ' ' : $text);
        } elseif (is_string($plain) && $html === null) {
            $message->html(nl2br(htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
        }
        return parent::send($message, $envelope);
    }

    protected function doSend(SentMessage $message): void
    {
        $from = (string) ($this->options['from_address'] ?? '');
        $domain = strtolower((string) ($this->options['domain'] ?? ''));
        $addresses = $message->getEnvelope()->getRecipients();
        if (!filter_var($from, FILTER_VALIDATE_EMAIL) || str_starts_with($from, '-') ||
            !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || strtolower(substr(strrchr($from, '@'), 1)) !== $domain ||
            count($addresses) !== 1) {
            throw new TransportException('Invalid local mail sender, domain or recipient count.');
        }
        $recipient = $addresses[0]->getAddress();
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || str_starts_with($recipient, '-') ||
            preg_match('/[\\r\\n\\x00]/', $recipient.$from)) {
            throw new TransportException('Invalid local mail address.');
        }
        if (strtolower($message->getEnvelope()->getSender()->getAddress()) !== strtolower($from)) {
            throw new TransportException('The envelope sender must match the configured local sender.');
        }
        $source = $message->getOriginalMessage();
        $fromHeaders = $source instanceof Email ? $source->getFrom() : [];
        if (count($fromHeaders) !== 1 || strtolower($fromHeaders[0]->getAddress()) !== strtolower($from)) {
            throw new TransportException('The visible sender must match the configured local sender.');
        }
        $subject = $source instanceof Email ? (string) $source->getSubject() : '';
        if ($subject === '' || preg_match('/[\\r\\n\\x00]/', $subject)) {
            throw new TransportException('Invalid local mail subject.');
        }
        $raw = $message->toString();
        if (strlen($raw) > 10485760) {
            throw new TransportException('Local mail exceeds the message size limit.');
        }
        $parts = preg_split('/\\r?\\n\\r?\\n/', $raw, 2);
        if (count($parts) !== 2) throw new TransportException('Invalid MIME message.');
        $headers = preg_replace('/^(?:To|Subject|Bcc|Cc):[^\\r\\n]*(?:\\r?\\n[ \\t][^\\r\\n]*)*\\r?\\n/mi', '', $parts[0]."\r\n");
        $encodedSubject = $source->getHeaders()->get('Subject')->getBodyAsString();
        $encodedSubject = preg_replace('/\\r?\\n[ \\t]+/', ' ', $encodedSubject);
        if (!function_exists('mail')) throw new TransportException('PHP mail is unavailable.');
        $mode = (string) ($this->options['envelope_mode'] ?? 'from');
        if (!in_array($mode, ['from', 'provider'], true)) throw new TransportException('Invalid local mail envelope mode.');
        if ($mode === 'from' && ini_get('mail.force_extra_parameters')) throw new TransportException('The server overrides the envelope sender.');
        $recipientEncrypted = Crypt::encryptString($recipient);
        $this->reserveQuota();
        // Never retry automatically: an ambiguous outcome could duplicate a message.
        $ok = $mode === 'from'
            ? @mail($recipient, $encodedSubject, $parts[1], rtrim($headers, "\r\n"), '-f'.escapeshellarg($from))
            : @mail($recipient, $encodedSubject, $parts[1], rtrim($headers, "\r\n"));
        try {
            Log::info('Local mail submission', ['recipient_encrypted' => $recipientEncrypted, 'status' => $ok ? 'accepted' : 'failed']);
        } catch (\Throwable $exception) {
            // A logging failure after local acceptance must not cause a duplicate retry.
        }
        if (!$ok) throw new TransportException('Local mail submission failed.');
    }

    private function reserveQuota(): void
    {
        $limit = (int) ($this->options['hourly_limit'] ?? 60);
        if ($limit < 1 || $limit > 10000) throw new TransportException('Invalid local mail rate limit.');
        $dir = storage_path('app/linkfree/local-mail');
        if (is_link($dir) || (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir))) {
            throw new TransportException('Local mail rate storage unavailable.');
        }
        $path = $dir.'/rate.json';
        if (is_link($path) || !($file = @fopen($path, 'c+'))) throw new TransportException('Local mail rate storage unavailable.');
        try {
            if (!flock($file, LOCK_EX)) throw new TransportException('Local mail rate lock unavailable.');
            $content = stream_get_contents($file);
            $events = $content === '' ? [] : json_decode($content, true);
            if (!is_array($events)) throw new TransportException('Local mail rate storage invalid.');
            $now = time();
            $events = array_values(array_filter($events, fn($value) => is_int($value) && $value > $now - 3600));
            if (count($events) >= $limit) throw new TransportException('Local mail hourly limit reached.');
            $events[] = $now;
            rewind($file);
            if (!ftruncate($file, 0) || fwrite($file, json_encode($events)) === false || !fflush($file)) {
                throw new TransportException('Local mail rate storage unavailable.');
            }
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    public function __toString(): string { return 'local-php-mail'; }
}
