<?php

namespace App\Services;

use GeoSot\EnvEditor\Facades\EnvEditor;

/**
 * Administrador decide se cada NOVO cadastro recebe o papel VIP.
 * A configuracao fica no .env persistente da instalacao; valor ausente = inativo.
 * Nunca afeta usuarios existentes ou a conta de bootstrap do instalador.
 */
final class RegistrationVip
{
    private const KEY = 'AUTO_VIP_NEW_USERS';

    public static function enabled(): bool
    {
        try {
            $value = EnvEditor::getKey(self::KEY);
        } catch (\Throwable $exception) {
            return false;
        }

        return in_array(strtolower(trim((string) $value, " \t\n\r\0\x0B\"'")), ['true', '1', 'yes', 'on'], true);
    }

    public static function roleForNewUser(): string
    {
        return self::enabled() ? 'vip' : 'user';
    }

    public static function setEnabled(bool $enabled): void
    {
        $value = $enabled ? 'true' : 'false';
        if (EnvEditor::keyExists(self::KEY)) {
            EnvEditor::editKey(self::KEY, $value);
        } else {
            EnvEditor::addKey(self::KEY, $value);
        }
        // Nao anunciar sucesso se o filesystem impedir a persistencia.
        if (self::enabled() !== $enabled) {
            throw new \RuntimeException('A configuracao AUTO_VIP_NEW_USERS nao foi persistida.');
        }
    }
}
