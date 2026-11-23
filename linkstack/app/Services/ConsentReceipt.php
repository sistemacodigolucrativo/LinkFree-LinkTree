<?php
namespace App\Services;

use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ConsentReceipt
{
    /** Hash do texto legal tal como publicado no momento do aceite. */
    public static function versions(): array
    {
        $page = Page::query()->first();
        return [
            'terms_version' => hash('sha256', (string) ($page->terms ?? '')),
            'privacy_version' => hash('sha256', (string) ($page->privacy ?? '')),
        ];
    }

    public static function record(User $user, string $method = 'email'): void
    {
        DB::table('registration_consents')->insert(array_merge(self::versions(), [
            'user_id' => $user->id,
            'method' => in_array($method, ['email', 'social'], true) ? $method : 'email',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}
