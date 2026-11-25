<?php

namespace App\Http\Controllers;

use App\Mail\ReportSubmissionMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AbuseReportController extends Controller
{
    private const CATEGORIES = [
        'hate_speech',
        'violence_threats',
        'illegal_activities',
        'copyright_infringement',
        'misinformation_fake_news',
        'identity_theft',
        'drug_related_content',
        'weapons_harmful_objects',
        'child_exploitation',
        'fraud_scams',
        'privacy_violation',
        'impersonation',
        'other_specify',
    ];

    private const STATUSES = ['new', 'in_review', 'resolved', 'dismissed'];

    public function form(Request $request)
    {
        // Support old public links in the form "/report?123456".
        $queryString = (string) $request->server('QUERY_STRING', '');
        $rawId = ctype_digit($queryString) ? $queryString : null;
        $userId = $request->query('user', $request->query('id', $rawId));
        $target = ctype_digit((string) $userId)
            ? User::whereKey($userId)->where('block', 'no')->first()
            : null;

        return view('report', [
            'target' => $target,
            'targetUrl' => $target && $target->littlelink_name
                ? route('littlelink', ['littlelink' => $target->littlelink_name])
                : '',
            'categories' => self::categoryLabels(),
        ]);
    }

    public function store(Request $request)
    {
        $categories = self::categoryLabels();
        // Existing localized form values are accepted for backward compatibility.
        $choices = array_unique(array_merge(array_keys($categories), array_values($categories)));

        $input = $request->validate([
            'url' => 'required|url|max:2048',
            'report-type' => ['required', 'string', Rule::in($choices)],
            'message' => 'nullable|string|max:2000',
            'user_id' => 'nullable|integer|min:1',
            'id' => 'nullable|integer|min:1',
            'website' => 'nullable|string|max:100',
        ]);

        // Silent bot-trap: no arbitrary values are stored, mailed, or displayed.
        if (!empty($input['website'])) {
            return redirect()->route('report.form')->with('success', true);
        }

        $rawCategory = $input['report-type'];
        $category = array_search($rawCategory, $categories, true);
        $category = $category === false ? $rawCategory : $category;

        $targetId = $input['user_id'] ?? $input['id'] ?? null;
        $target = $targetId !== null
            ? User::whereKey($targetId)->where('block', 'no')->first()
            : null;

        // Never trust the submitted handle/name/URL as proof of profile identity.
        $canonicalUrl = $target && $target->littlelink_name
            ? route('littlelink', ['littlelink' => $target->littlelink_name])
            : null;
        $url = $canonicalUrl ?: $input['url'];

        $reportId = DB::transaction(function () use ($target, $url, $category, $input) {
            $now = now();
            $id = DB::table('abuse_reports')->insertGetId([
                'reported_user_id' => $target ? $target->id : null,
                'reporter_user_id' => Auth::id(),
                'reported_handle' => $target ? $target->littlelink_name : null,
                'reported_url' => $url,
                'category' => $category,
                'message' => trim((string) ($input['message'] ?? '')) ?: null,
                'status' => 'new',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('abuse_report_events')->insert([
                'abuse_report_id' => $id,
                'actor_user_id' => Auth::id(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => 'new',
                'note' => null,
                'created_at' => $now,
            ]);
            return $id;
        });

        // E-mail is a best-effort notification. A mail server outage does not
        // lose the report, which is already safely persisted in the database.
        $mailTo = env('ADMIN_EMAIL');
        if (is_string($mailTo) && filter_var($mailTo, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($mailTo)->send(new ReportSubmissionMail([
                    'report_id' => $reportId,
                    'url' => $url,
                    'report-type' => $categories[$category] ?? $category,
                    'message' => $input['message'] ?? '',
                ]));
            } catch (\Throwable $error) {
                report($error);
            }
        }

        return redirect()->route('report.form')->with('success', true);
    }

    public function index(Request $request)
    {
        $input = Validator::make($request->query(), [
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ])->validate();

        $status = $input['status'] ?? null;
        $query = DB::table('abuse_reports as reports')
            ->leftJoin('users as target', 'target.id', '=', 'reports.reported_user_id')
            ->select(
                'reports.*',
                'target.name as current_target_name'
            );
        if ($status !== null) {
            $query->where('reports.status', $status);
        }

        $totals = DB::table('abuse_reports')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('panel.abuse-reports', [
            'reports' => $query->orderByDesc('reports.created_at')->paginate(20)->withQueryString(),
            'status' => $status,
            'statuses' => self::statusLabels(),
            'categories' => self::categoryLabels(),
            'counts' => $totals,
            'totalCount' => (int) $totals->sum(),
        ]);
    }

    public function show(int $id)
    {
        $record = DB::table('abuse_reports as reports')
            ->leftJoin('users as target', 'target.id', '=', 'reports.reported_user_id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'reports.reviewed_by')
            ->where('reports.id', $id)
            ->select(
                'reports.*',
                'target.name as current_target_name',
                'reviewer.name as reviewer_name'
            )->first();
        abort_unless($record, 404);

        $events = DB::table('abuse_report_events as events')
            ->leftJoin('users as actor', 'actor.id', '=', 'events.actor_user_id')
            ->where('events.abuse_report_id', $id)
            ->select('events.*', 'actor.name as actor_name')
            ->orderBy('events.id')
            ->paginate(15)->withQueryString();

        return view('panel.abuse-report-detail', [
            'record' => $record,
            'events' => $events,
            'statuses' => self::statusLabels(),
            'categories' => self::categoryLabels(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $input = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'note' => 'nullable|string|max:2000',
        ]);
        $note = trim((string) ($input['note'] ?? '')) ?: null;

        // Every closing decision must have a reason for traceability.
        if (in_array($input['status'], ['resolved', 'dismissed'], true) && !$note) {
            return back()->withErrors(['note' => 'Informe a justificativa para encerrar a denúncia.'])
                ->withInput();
        }

        DB::transaction(function () use ($id, $input, $note) {
            $record = DB::table('abuse_reports')->where('id', $id)->lockForUpdate()->first();
            abort_unless($record, 404);
            $newStatus = $input['status'];

            if ($record->status === $newStatus && !$note) {
                return;
            }

            $now = now();
            DB::table('abuse_reports')->where('id', $id)->update([
                'status' => $newStatus,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('abuse_report_events')->insert([
                'abuse_report_id' => $id,
                'actor_user_id' => Auth::id(),
                'action' => $record->status === $newStatus ? 'noted' : 'status_changed',
                'from_status' => $record->status,
                'to_status' => $newStatus,
                'note' => $note,
                'created_at' => $now,
            ]);
        });

        return redirect()->route('admin.abuse-reports.show', ['id' => $id])
            ->with('success', 'Denúncia atualizada. A decisão foi registrada no histórico.');
    }

    private static function categoryLabels(): array
    {
        $labels = [];
        foreach (self::CATEGORIES as $key) {
            $labels[$key] = __('messages.' . $key);
        }
        return $labels;
    }

    private static function statusLabels(): array
    {
        return [
            'new' => 'Nova',
            'in_review' => 'Em análise',
            'resolved' => 'Resolvida',
            'dismissed' => 'Arquivada',
        ];
    }
}
