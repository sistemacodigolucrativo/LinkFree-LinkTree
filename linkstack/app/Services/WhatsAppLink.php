<?php
namespace App\Services;

use Illuminate\Validation\ValidationException;

final class WhatsAppLink
{
    public static function build(string $country, string $number, string $message): string
    {
        if (!preg_match('/^\+?[0-9]{1,3}$/D', trim($country)) ||
            !preg_match('/^[0-9() .+-]{8,24}$/D', trim($number))) {
            throw ValidationException::withMessages(['wa_phone' => __('messages.lf_wa_invalid_phone')]);
        }
        $digits = preg_replace('/\D+/', '', $country.$number);
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            throw ValidationException::withMessages(['wa_phone' => __('messages.lf_wa_invalid_phone')]);
        }
        return 'https://wa.me/'.$digits.($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}
