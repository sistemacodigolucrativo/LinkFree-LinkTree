<?php

namespace App\Http\Controllers;

use App\Services\PlatformReviewPublication;
use App\Services\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class PlatformReviewController extends Controller
{
    private function requireSchema(): void
    {
        abort_unless(Schema::hasTable('platform_reviews'), 503, 'Recurso indisponível até a migração autorizada.');
    }

    public function member(Request $request)
    {
        $this->requireSchema();

        return view('studio.platform-review', [
            'existingReview' => DB::table('platform_reviews')->where('user_id', Auth::id())->first(),
            'contributionUrl' => PlatformReviewPublication::contributionUrl(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireSchema();
        $data = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'testimonial' => 'nullable|string|max:1200',
            'publication_consent' => 'required|accepted',
            'website' => 'nullable|string|max:200',
        ]);

        // Honeypot. No review is created, even when a bot supplied valid consent.
        if (trim((string) ($data['website'] ?? '')) !== '') {
            return redirect()->route('studio.platform-review');
        }

        $message = trim((string) ($data['testimonial'] ?? ''));
        DB::transaction(function () use ($data, $message) {
            // Serialize member submissions even on concurrent HTTP requests.
            abort_unless(DB::table('users')->where('id', Auth::id())->lockForUpdate()->first(), 404);
            $previous = DB::table('platform_reviews')->where('user_id', Auth::id())->first();
            if ($previous && $previous->status !== 'rejected') {
                throw ValidationException::withMessages([
                    'rating' => __('reviews.existing_review'),
                ]);
            }
            if ($previous && now()->diffInHours($previous->updated_at) < 24) {
                throw ValidationException::withMessages([
                    'rating' => __('reviews.retry_later'),
                ]);
            }

            $values = [
                'rating' => (int) $data['rating'],
                'testimonial' => $message === '' ? null : $message,
                'publication_consent' => true,
                'consented_at' => now(),
                'consent_version' => '2026-11-26',
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'updated_at' => now(),
            ];
            if ($previous) {
                DB::table('platform_reviews')->where('id', $previous->id)->update($values);
            } else {
                DB::table('platform_reviews')->insert(array_merge($values, [
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ]));
            }
        });

        return redirect()->route('studio.platform-review')->with('success', __('reviews.received'));
    }

    public function withdraw(Request $request)
    {
        $this->requireSchema();
        DB::table('platform_reviews')->where('user_id', Auth::id())->delete();

        return redirect()->route('studio.platform-review')->with('success', __('reviews.withdrawn'));
    }

    public function publicPage(Request $request)
    {
        abort_if(PublicSeo::customDomain($request) !== null, 404);

        return view('public.platform-reviews', [
            'reviews' => PlatformReviewPublication::published(24),
        ]);
    }

    public function admin()
    {
        $this->requireSchema();

        return view('panel.platform-reviews', [
            'reviews' => DB::table('platform_reviews as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->select('r.*', 'u.name as member_name', 'u.email as member_email')
                ->orderByRaw("CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END")
                ->orderByDesc('r.created_at')
                ->paginate(20),
        ]);
    }

    public function moderate(Request $request, int $id)
    {
        $this->requireSchema();
        $data = $request->validate(['decision' => 'required|in:approve,reject,hide']);

        DB::transaction(function () use ($id, $data) {
            $review = DB::table('platform_reviews')->where('id', $id)->lockForUpdate()->first();
            abort_unless($review, 404);
            $decision = $data['decision'];
            abort_unless(
                ($review->status === 'pending' && in_array($decision, ['approve', 'reject'], true))
                || ($review->status === 'approved' && $decision === 'hide'),
                409,
                __('reviews.already_reviewed')
            );
            if ($decision === 'approve') {
                abort_unless((bool) $review->publication_consent && $review->consented_at !== null, 409);
            }
            DB::table('platform_reviews')->where('id', $id)->update([
                'status' => ['approve' => 'approved', 'reject' => 'rejected', 'hide' => 'hidden'][$decision],
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('admin.platform-reviews')->with('success', __('reviews.moderated'));
    }
}
