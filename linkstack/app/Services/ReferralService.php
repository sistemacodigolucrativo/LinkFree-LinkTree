<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReferralService
{
    public const SESSION_KEY = 'lf_pending_referral';
    private const MAX_AGE_SECONDS = 604800;

    /**
     * Stable invite code per member. Atomic insert-or-ignore handles parallel requests,
     * while the database enforces one code per user and globally unique codes.
     */
    public static function ensureCode(User $user): string
    {
        $code = DB::table('referral_codes')->where('user_id', $user->id)->value('code');
        if ($code) {
            return $code;
        }

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $candidate = bin2hex(random_bytes(12));
            DB::table('referral_codes')->insertOrIgnore([
                'user_id' => $user->id,
                'code' => $candidate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $code = DB::table('referral_codes')->where('user_id', $user->id)->value('code');
            if ($code) {
                return $code;
            }
        }

        throw new RuntimeException('Não foi possível gerar um código de indicação único.');
    }

    /**
     * Invite links only record an invitation in the same browser session;
     * existing accounts are never retrospectively assigned.
     */
    public static function capture(string $code, Session $session): bool
    {
        $valid = DB::table('referral_codes as codes')
            ->join('users as owner', 'owner.id', '=', 'codes.user_id')
            ->where('codes.code', strtolower($code))
            ->where('owner.block', 'no')
            ->exists();

        if (!$valid) {
            return false;
        }

        $session->put(self::SESSION_KEY, [
            'code' => strtolower($code),
            'at' => time(),
        ]);

        return true;
    }

    /**
     * Call only while creating a new user, inside the existing registration
     * transaction. The unique invited_user_id constraint prevents duplicates.
     */
    public static function record(User $newUser, Session $session): void
    {
        $pending = $session->get(self::SESSION_KEY);
        if (!is_array($pending) ||
            !isset($pending['code'], $pending['at']) ||
            !is_string($pending['code']) ||
            !ctype_xdigit($pending['code']) ||
            strlen($pending['code']) !== 24 ||
            (int) $pending['at'] > time() ||
            time() - (int) $pending['at'] > self::MAX_AGE_SECONDS) {
            return;
        }

        $ownerId = DB::table('referral_codes as codes')
            ->join('users as owner', 'owner.id', '=', 'codes.user_id')
            ->where('codes.code', $pending['code'])
            ->where('owner.block', 'no')
            ->value('codes.user_id');

        if (!$ownerId || (int) $ownerId === (int) $newUser->id) {
            return;
        }

        DB::table('referrals')->insertOrIgnore([
            'referrer_user_id' => $ownerId,
            'invited_user_id' => $newUser->id,
            'referral_code' => $pending['code'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
