<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    public function member()
    {
        return view('studio.verification', [
            'member' => Auth::user()->fresh(),
            'application' => DB::table('verification_requests')->where('user_id', Auth::id())->first(),
        ]);
    }

    public function apply(Request $request)
    {
        $data = $request->validate(['message' => 'nullable|string|max:700']);

        $result = DB::transaction(function () use ($data) {
            $user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
            if ($user->verified_at !== null) {
                return 'Seu selo já foi aprovado.';
            }

            $current = DB::table('verification_requests')->where('user_id', $user->id)->first();
            if ($current && $current->status === 'pending') {
                return 'Você já possui uma solicitação aguardando análise.';
            }

            $values = [
                'status' => 'pending',
                'message' => trim((string) ($data['message'] ?? '')) ?: null,
                'admin_response' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'requested_at' => now(),
                'updated_at' => now(),
            ];
            if ($current) {
                DB::table('verification_requests')->where('user_id', $user->id)->update($values);
            } else {
                DB::table('verification_requests')->insert(array_merge($values, [
                    'user_id' => $user->id,
                    'created_at' => now(),
                ]));
            }

            self::audit($user->id, $user->id, 'requested');
            return 'Solicitação enviada ao administrador.';
        });

        return redirect()->route('studio.verification')->with('success', $result);
    }

    public function visibility(Request $request)
    {
        $data = $request->validate(['visible' => 'required|boolean']);
        $user = Auth::user()->fresh();
        abort_unless($user && $user->verified_at !== null, 403, 'Selo não aprovado.');

        User::whereKey($user->id)->whereNotNull('verified_at')->update([
            'verification_visible' => (bool) $data['visible'],
        ]);

        return redirect()->route('studio.verification')
            ->with('success', $data['visible'] ? 'Selo visível no perfil.' : 'Selo ocultado no perfil.');
    }

    public function admin()
    {
        $applications = DB::table('verification_requests as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'v.reviewed_by')
            ->select('v.*', 'u.name as member_name', 'u.email as member_email',
                'u.littlelink_name as member_handle', 'reviewer.name as reviewer_name')
            ->orderByRaw("CASE WHEN v.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('v.requested_at')
            ->paginate(20);

        $history = DB::table('verification_audit_events as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.user_id')
            ->leftJoin('users as a', 'a.id', '=', 'h.actor_id')
            ->select('h.*', 'u.name as member_name', 'a.name as actor_name')
            ->orderByDesc('h.created_at')
            ->limit(30)
            ->get();

        return view('panel.verification', [
            'applications' => $applications,
            'history' => $history,
            'pendingCount' => DB::table('verification_requests')->where('status', 'pending')->count(),
            'approvedCount' => User::whereNotNull('verified_at')->count(),
        ]);
    }

    public function review(Request $request, int $id)
    {
        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'response' => 'nullable|string|max:1000|required_if:decision,reject',
        ]);
        $response = trim((string) ($data['response'] ?? '')) ?: null;
        $approval = $data['decision'] === 'approve';

        DB::transaction(function () use ($id, $response, $approval) {
            $record = DB::table('verification_requests')->where('id', $id)->first();
            abort_unless($record, 404);
            $user = User::whereKey($record->user_id)->lockForUpdate()->firstOrFail();
            $locked = DB::table('verification_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless($locked && $locked->status === 'pending', 409, 'Solicitação já analisada.');

            $user->verified_at = $approval ? now() : null;
            $user->verification_visible = $approval;
            $user->save();

            DB::table('verification_requests')->where('id', $id)->update([
                'status' => $approval ? 'approved' : 'rejected',
                'admin_response' => $response,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            self::audit($user->id, Auth::id(), $approval ? 'approved' : 'rejected', $response);
        });

        return redirect()->route('admin.verification')
            ->with('success', $approval ? 'Selo aprovado.' : 'Solicitação recusada.');
    }

    public function toggle(Request $request, int $user)
    {
        $data = $request->validate(['approved' => 'required|boolean']);
        $approved = (bool) $data['approved'];

        DB::transaction(function () use ($user, $approved) {
            $member = User::whereKey($user)->lockForUpdate()->firstOrFail();
            if (($member->verified_at !== null) === $approved) {
                return;
            }

            $member->verified_at = $approved ? now() : null;
            $member->verification_visible = $approved;
            $member->save();

            DB::table('verification_requests')
                ->where('user_id', $member->id)
                ->whereIn('status', $approved ? ['pending', 'rejected', 'revoked'] : ['approved'])
                ->update([
                    'status' => $approved ? 'approved' : 'revoked',
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'admin_response' => $approved
                        ? 'Aprovado diretamente pelo administrador.'
                        : 'Selo revogado diretamente pelo administrador.',
                    'updated_at' => now(),
                ]);

            self::audit($member->id, Auth::id(), $approved ? 'approved_direct' : 'revoked');
        });

        return redirect()->back()->with('success', $approved
            ? 'Selo ativado para o membro.'
            : 'Selo desativado para o membro.');
    }

    private static function audit(int $userId, int $actorId, string $action, ?string $note = null): void
    {
        DB::table('verification_audit_events')->insert([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'action' => $action,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
