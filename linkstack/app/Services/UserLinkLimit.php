<?php
namespace App\Services;

use App\Models\Link;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UserLinkLimit
{
    public const FREE_LIMIT = 5;

    public static function isCounted($link): bool
    {
        // Blocos decorativos nao sao links de navegacao.
        $type = (string) ($link->type ?? '');
        return !in_array($type, ['heading', 'spacer', 'text'], true);
    }

    public static function count(int $userId): int
    {
        return Link::where('user_id', $userId)->where(function ($query) {
            $query->whereNull('type')->orWhereNotIn('type', ['heading', 'spacer', 'text']);
        })->count();
    }

    public static function ensureAllowed(User $user, $link): void
    {
        if ($user->role === 'user' && self::isCounted($link) &&
            self::count((int) $user->id) >= self::FREE_LIMIT) {
            throw ValidationException::withMessages(['link_limit' => __('messages.lf_limit_reached')]);
        }
    }

    public static function countImportable(array $links): int
    {
        $count = 0;
        foreach ($links as $data) {
            if (!is_array($data)) throw ValidationException::withMessages([
                'import' => __('messages.lf_limit_reached'),
            ]);
            if (self::isCounted((object) $data)) $count++;
        }
        return $count;
    }
}
