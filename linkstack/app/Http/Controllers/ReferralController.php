<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    public function visit(Request $request, string $code)
    {
        if (Auth::check()) {
            return redirect()->route('panelIndex')
                ->with('referral_notice', 'As indicações são válidas apenas para novos cadastros.');
        }

        abort_unless(ReferralService::capture($code, $request->session()), 404);

        return redirect()->route('register');
    }

    public function index()
    {
        $userId = Auth::id();
        $code = DB::table('referral_codes')->where('user_id', $userId)->value('code');
        $history = DB::table('referrals as r')
            ->leftJoin('users as invited', 'invited.id', '=', 'r.invited_user_id')
            ->where('r.referrer_user_id', $userId)
            ->select('r.id', 'r.created_at', 'invited.littlelink_name')
            ->orderByDesc('r.created_at')
            ->paginate(15);

        return view('studio.referrals', [
            'code' => $code,
            'history' => $history,
            'total' => DB::table('referrals')->where('referrer_user_id', $userId)->count(),
        ]);
    }

    public function createCode()
    {
        ReferralService::ensureCode(Auth::user());

        return redirect()->route('studio.referrals')->with('success', 'Seu link de convite está pronto.');
    }

    public function adminIndex(Request $request)
    {
        $validated = $request->validate(['referrer_id' => 'nullable|integer|min:1']);
        $ownerId = $validated['referrer_id'] ?? null;

        $query = DB::table('referrals as r')
            ->leftJoin('users as owner', 'owner.id', '=', 'r.referrer_user_id')
            ->leftJoin('users as invited', 'invited.id', '=', 'r.invited_user_id')
            ->select(
                'r.id',
                'r.referrer_user_id',
                'r.invited_user_id',
                'r.created_at',
                'r.referral_code',
                'owner.name as referrer_name',
                'owner.email as referrer_email',
                'invited.name as invited_name',
                'invited.email as invited_email'
            );

        if ($ownerId !== null) {
            $query->where('r.referrer_user_id', $ownerId);
        }

        return view('panel.referrals', [
            'history' => $query->orderByDesc('r.created_at')->paginate(20)->withQueryString(),
            'total' => DB::table('referrals')->count(),
            'last30Days' => DB::table('referrals')->where('created_at', '>=', now()->subDays(30))->count(),
            'activeInviters' => DB::table('referrals')->whereNotNull('referrer_user_id')
                ->distinct('referrer_user_id')->count('referrer_user_id'),
            'ownerId' => $ownerId,
        ]);
    }
}
