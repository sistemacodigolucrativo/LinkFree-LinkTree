#!/usr/bin/env python3
"""Contract guards for LinkFree reviews and dynamic branding. Source-only; never connect to production."""
from pathlib import Path

root = Path(__file__).resolve().parents[1]
def read(path):
    return (root / path).read_text(encoding="utf-8")
def require(condition, title):
    assert condition, title
    print("PASS:", title, flush=True)

app = read("linkstack/app/Http/Controllers/PlatformReviewController.php")
route = read("linkstack/routes/web.php")
notify = read("linkstack/resources/views/layouts/notifications.blade.php")
service = read("linkstack/app/Services/PlatformReviewPublication.php")
schema = read("linkstack/database/migrations/2026_11_26_000001_create_platform_reviews_table.php")
form = read("linkstack/resources/views/studio/platform-review.blade.php")
public = read("linkstack/resources/views/public/platform-reviews.blade.php")
admin = read("linkstack/resources/views/panel/platform-reviews.blade.php")
css = read("linkstack/assets/css/linkfree-platform-reviews.css")
deployment = read("scripts/atualizar-linkstack.sh")
marketing = read("linkstack/app/Services/PublicSiteContent.php")
catalog = read("linkstack/resources/views/public/catalog.blade.php")
login = read("linkstack/resources/views/auth/login.blade.php")
homepage = read("linkstack/resources/views/home.blade.php")

require("'notification_link' => 'Deixe uma avaliação'" in read("linkstack/resources/lang/pt-BR/reviews.php")
        and "route('studio.platform-review')" in notify
        and "linkstack.org/donate" not in notify
        and "github.com/linkstackorg/linkstack" not in notify,
        "Member notification opens the local review page and not upstream donation links")
require("throttle:3,60" in route and "'auth', 'blocked', 'impersonate'" in route
        and "Route::post('/studio/avaliar'" in route
        and "Route::post('/studio/avaliar/retirar'" in route
        and "Route::post('/admin/avaliacoes/{id}/moderar'" in route,
        "Review submission, withdrawal, and moderation use protected POST routes")
require("$request->validate" in app
        and "'rating' => 'required|integer|between:1,5'" in app
        and "'publication_consent' => 'required|accepted'" in app
        and "'website' => 'nullable|string|max:200'" in app
        and "lockForUpdate()" in app
        and "Auth::id()" in app,
        "Server validates score, explicit consent, spam trap and member ownership")
require("'status' => 'pending'" in app
        and "where('status', 'approved')" in service
        and "where('publication_consent', true)" in service
        and "whereNotNull('consented_at')" in service
        and "DB::table('platform_reviews')->where('user_id', Auth::id())->delete()" in app,
        "Reviews are unpublished until moderation and can be fully withdrawn by the member")
require("$table->unsignedBigInteger('user_id')->unique()" in schema
        and "$table->boolean('publication_consent')->default(false)" in schema
        and "$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')" in schema,
        "New schema is additive, one review per member, and deletes with the account")
require("{{ $review->testimonial }}" in public
        and "select('rating', 'testimonial', 'created_at')" in service
        and "reviewed_by" not in public
        and "member_email" not in public
        and "e(__('reviews.notification_title'" in notify,
        "Public output is anonymous and escaped; notification contains escaped dynamic brand")
require("data-confirm=" in form
        and "name=\"rating\"" in form
        and "name=\"publication_consent\"" in form
        and "@csrf" in form
        and "route('admin.platform-reviews.moderate'" in admin
        and "lf-review-stars" in css and "@media(max-width:480px)" in css,
        "Accessible star/consent UI, moderation action and mobile CSS exist")
require("voluntary_contribution_url" in service
        and "strtolower($parts['scheme'] ?? '') !== 'https'" in service
        and "linkstack.org/donate" not in service,
        "Contribution destination is optional, HTTPS-only and not invented")
require("config('app.name'" in marketing and "str_replace('LinkFree', (string) config('app.name'" in marketing
        and "array_intersect_key($stored, $defaults)" in marketing,
        "Existing public copy respects APP_NAME and preserves CMS overrides")
require("LINKFREE</p>" not in login and "Veja os recursos atuais do LinkFree" not in catalog
        and "Prévia do LinkFree" not in homepage
        and "O que vem a seguir no LinkFree?" not in homepage,
        "Visible fixed-brand labels were removed from existing public surfaces")
for locale in ['ar','bg','de','el','en','es','fr','gl','pt-BR','ru','sv','vi','zh-TW','zh']:
    messages = read(f"linkstack/resources/lang/{locale}/messages.php")
    require("config('app.name', 'LinkFree')" in messages
            and "return $messages;" in messages
            and "Link\\s*Stacks?" in messages,
            f"Brand-bearing interface translations are dynamic for {locale}")
require("REVIEW_MIGRATION='database/migrations/2026_11_26_000001_create_platform_reviews_table.php'" in deployment
        and "--deploy-reviews" in deployment
        and 'if [[ "$MODE" == --deploy-reviews ]]; then' in deployment
        and '"$backup/sqlite-before-deploy.sqlite"' in deployment
        and "PRAGMA foreign_key_check" in deployment,
        "New additive migration requires explicit guarded deployment with online backup")
# Homologação funcional: paleta do painel e segmentação da notificação.
require("auth()->user()->role !== 'admin'" in notify
        and "reviews.admin_no_notifications" in notify
        and "reviews.notification_title" in notify
        and "'admin_no_notifications'" in read("linkstack/resources/lang/pt-BR/reviews.php")
        and "'admin_no_notifications'" in read("linkstack/resources/lang/en/reviews.php"),
        "Admin does not receive member review solicitation and empty notifications are localized")
require("body.dark .main-content .lf-review-page" in css
        and "--lf-canvas" in css
        and "--lf-surface" in css
        and "--lf-text" in css
        and "--lf-muted" in css
        and "color: #ffcc36 !important" in css
        and "background-color: #263854" in css,
        "Review form follows dashboard palette and selected stars are contrast-safe yellow")
require("starContrast" in read("scripts/qa-isolated/member_navigation_browser_smoke.cjs")
        and "admin_no_notifications" in notify
        and "Nenhuma notificação pendente" in read("linkstack/resources/lang/pt-BR/reviews.php"),
        "Authenticated browser regression tests color contrast and admin empty state")

print("PASS: review/privacy/branding/deployment source-level contracts")
