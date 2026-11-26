<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\AbuseReportController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\LinkTypeViewController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Prevents section below from being run by 'composer update'
if(file_exists(base_path('storage/app/ISINSTALLED'))){
  // generates new APP KEY if no one is set
  if(EnvEditor::getKey('APP_KEY')==''){try{Artisan::call('key:generate');} catch (exception $e) {}}

  // copies template meta config if none is present
  if(!file_exists(base_path("config/advanced-config.php"))){copy(base_path('storage/templates/advanced-config.php'), base_path('config/advanced-config.php'));}
 }

 // Installer
if(file_exists(base_path('INSTALLING')) or file_exists(base_path('INSTALLERLOCK'))){

  Route::get('/', [InstallerController::class, 'showInstaller'])->name('showInstaller');
  Route::post('/create-admin', [InstallerController::class, 'createAdmin'])->name('createAdmin');
  Route::post('/db', [InstallerController::class, 'db'])->name('db');
  Route::post('/mysql', [InstallerController::class, 'mysql'])->name('mysql');
  Route::post('/options', [InstallerController::class, 'options'])->name('options');
  // Database initialization now occurs in the CSRF-protected /mysql POST.
  Route::get('/mysql-test', fn () => abort(405));
  // The VPS installer already denies this default-account shortcut at nginx.
  Route::get('/skip', fn () => abort(404));
  Route::post('/editConfigInstaller', [InstallerController::class, 'editConfigInstaller'])->name('editConfigInstaller');

  // Reading a URL must never complete installation or remove a marker.
  Route::get('{any}', fn () => abort(404))->where('any', '.*');
  // No normal login, registration, OAuth or administrative routes until setup is finished.
  return;

}else{

// Disables routes if in Maintenance Mode
if(env('MAINTENANCE_MODE') != 'true'){

require __DIR__.'/home.php';

// Páginas institucionais públicas, sem sessão ou escrita, antes do catch-all de perfis.
// Prefixo com dois segmentos evita capturar identificadores públicos /{littlelink}.
Route::get('/plataforma/recursos', [\App\Http\Controllers\PublicCatalogController::class, 'resources'])
    ->name('public.resources')->middleware('disableCookies');
Route::get('/plataforma/proximas-implementacoes', [\App\Http\Controllers\PublicCatalogController::class, 'roadmap'])
    ->name('public.roadmap')->middleware('disableCookies');


// SEO and public social previews; unavailable in installer/maintenance mode.
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots'])->name('seo.robots')->middleware('disableCookies');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('seo.sitemap')->middleware('disableCookies');
Route::get('/sitemap-home.xml', [\App\Http\Controllers\SeoController::class, 'homeSitemap'])->name('seo.sitemap.home')->middleware('disableCookies');
Route::get('/sitemap-profiles/{page}.xml', [\App\Http\Controllers\SeoController::class, 'profilesSitemap'])->where('page', '[1-9][0-9]{0,7}')->name('seo.sitemap.profiles')->middleware('disableCookies');
Route::get('/social-preview/site.jpg', [\App\Http\Controllers\SocialPreviewController::class, 'site'])->name('socialPreview.site')->middleware(['disableCookies','throttle:60,1']);
Route::get('/social-preview/profile/{id}.jpg', [\App\Http\Controllers\SocialPreviewController::class, 'profile'])->where('id', '[0-9]{1,18}')->name('socialPreview.profile')->middleware(['disableCookies','throttle:60,1']);
Route::get('/social-preview/profile/{id}-wide.jpg', [\App\Http\Controllers\SocialPreviewController::class, 'profileWide'])->where('id', '[0-9]{1,18}')->name('socialPreview.profile.wide')->middleware(['disableCookies','throttle:60,1']);



//Redirect if no page URL is set
Route::get('/@', function () {
    return redirect('/studio/no_page_name');
});

//Show diagnose page
Route::get('/panel/diagnose', function () {
        return redirect()->route('admin.config.section', ['section' => 'diagnostics']);
})->middleware(['auth', 'blocked', 'impersonate', 'admin']);

//Public route
$custom_prefix = config('advanced-config.custom_url_prefix');
Route::get('/invite/{code}', [ReferralController::class, 'visit'])->name('referrals.visit')->where('code', '[A-Fa-f0-9]{24}');
Route::get('/going/{id?}', [UserController::class, 'clickNumber'])->where('link', '.*')->name('clickNumber')->middleware('disableCookies');
Route::get('/info/{id?}', [AdminController::class, 'redirectInfo'])->name('redirectInfo');
if($custom_prefix != ""){Route::get('/' . $custom_prefix . '{littlelink}', [UserController::class, 'littlelink'])->name('littlelink.custom-prefix');}
Route::get('/@{littlelink}', [UserController::class, 'littlelink'])->name('littlelink')->middleware('disableCookies');
Route::get('/pages/'.strtolower(footer('Terms')), [AdminController::class, 'pagesTerms'])->name('pagesTerms')->middleware('disableCookies');
Route::get('/pages/'.strtolower(footer('Privacy')), [AdminController::class, 'pagesPrivacy'])->name('pagesPrivacy')->middleware('disableCookies');
Route::get('/pages/'.strtolower(footer('Contact')), [AdminController::class, 'pagesContact'])->name('pagesContact')->middleware('disableCookies');
Route::get('/theme/@{littlelink}', [UserController::class, 'theme'])->name('theme');
Route::get('/vcard/{id?}', [UserController::class, 'vcard'])->name('vcard');
Route::get('/u/{id?}', [UserController::class, 'userRedirect'])->name('userRedirect');

Route::get('/report', [AbuseReportController::class, 'form'])->name('report.form');
Route::post('/report', [AbuseReportController::class, 'store'])->name('report')->middleware('throttle:5,10');

Route::get('/demo-page', [App\Http\Controllers\HomeController::class, 'demo'])->name('demo')->middleware('disableCookies');

Route::get('/block-asset/{type}', [LinkTypeViewController::class, 'blockAsset'])
  ->name('block.asset')->where(['type' => '[a-zA-Z0-9_-]+']);

}

Route::middleware(['auth', 'blocked', 'impersonate'])->group(function () {
//User route
Route::group([
    'middleware' => array_values(array_filter([env('REGISTER_AUTH')])),
], function () {
if(env('FORCE_ROUTE_HTTPS') == 'true'){URL::forceScheme('https');}
if(isset($_COOKIE['LinkCount'])){if($_COOKIE['LinkCount'] == '20'){$LinkPage = 'showLinks20';}elseif($_COOKIE['LinkCount'] == '30'){$LinkPage = 'showLinks30';}elseif($_COOKIE['LinkCount'] == 'all'){$LinkPage = 'showLinksAll';} else {$LinkPage = 'showLinks';}} else {$LinkPage = 'showLinks';} //Shows correct link number
Route::get('/dashboard', [AdminController::class, 'index'])->name('panelIndex');
Route::get('/studio/index', function(){return redirect(url('dashboard'));});
Route::get('/studio/add-link', [UserController::class, 'AddUpdateLink'])->name('showButtons');
Route::post('/studio/edit-link', [UserController::class, 'saveLink'])->name('addLink');
Route::get('/studio/edit-link/{id}', [UserController::class, 'AddUpdateLink'])->name('showLink')->middleware('link-id');
Route::post('/studio/sort-link', [UserController::class, 'sortLinks'])->name('sortLinks');
Route::get('/studio/links', [UserController::class, $LinkPage])->name($LinkPage);
Route::get('/studio/page-icons', [UserController::class, 'showIcons'])->name('showPageIcons');
Route::get('/studio/blocks', [UserController::class, 'showBlocks'])->name('showBlocks');
Route::get('/studio/theme', [UserController::class, 'showTheme'])->name('showTheme');
Route::post('/studio/theme-options', [UserController::class, 'saveThemeOptions'])->name('studio.theme-options');
Route::get('/studio/visitors', [UserController::class, 'showVisitorReports'])->name('studio.visitors');
Route::get('/studio/referrals', [ReferralController::class, 'index'])->name('studio.referrals');
Route::get('/studio/verification', [VerificationController::class, 'member'])->name('studio.verification');
Route::post('/studio/verification/request', [VerificationController::class, 'apply'])->name('studio.verification.apply')->middleware('throttle:5,60');
Route::post('/studio/verification/visibility', [VerificationController::class, 'visibility'])->name('studio.verification.visibility');
Route::post('/studio/referrals/code', [ReferralController::class, 'createCode'])->name('studio.referrals.code')->middleware('throttle:10,1');
Route::get('/studio/theme-preview/{theme}', [\App\Http\Controllers\ThemePreviewController::class, 'show'])->name('themePreview')->where('theme', '[A-Za-z][A-Za-z0-9_-]*');
Route::post('/studio/theme', [UserController::class, 'editTheme'])->name('editTheme');
Route::delete('/deleteLink/{id}', [UserController::class, 'deleteLink'])->name('deleteLink')->middleware('link-id');
Route::post('/upLink/{up}/{id}', [UserController::class, 'upLink'])->name('upLink')->middleware('link-id');
Route::post('/studio/edit-link/{id}', [UserController::class, 'editLink'])->name('editLink')->middleware('link-id');
Route::get('/studio/button-editor/{id}', [UserController::class, 'showCSS'])->name('showCSS')->middleware('link-id');
Route::post('/studio/button-editor/{id}', [UserController::class, 'editCSS'])->name('editCSS')->middleware('link-id');
Route::get('/studio/page', [UserController::class, 'showPage'])->name('showPage');
Route::get('/studio/no_page_name', [UserController::class, 'showPage'])->name('showPageMissingName');
Route::post('/studio/page', [UserController::class, 'editPage'])->name('editPage');
Route::post('/studio/background', [UserController::class, 'themeBackground'])->name('themeBackground');
Route::post('/studio/rem-background', [UserController::class, 'removeBackground'])->name('removeBackground');
Route::get('/studio/profile', [UserController::class, 'showProfile'])->name('showProfile');
Route::post('/studio/profile', [UserController::class, 'editProfile'])->name('editProfile');
// Returning to the form URL with GET must not resubmit changes.
Route::get('/edit-icons', fn () => redirect()->route('showPageIcons'));
Route::post('/edit-icons', [UserController::class, 'editIcons'])->name('editIcons');
Route::post('/clearIcon/{id}', [UserController::class, 'clearIcon'])->name('clearIcon')->middleware('link-id');
Route::post('/studio/page/delprofilepicture', [UserController::class, 'delProfilePicture'])->name('delProfilePicture');
Route::delete('/studio/delete-user/{id}', [UserController::class, 'deleteUser'])->name('deleteOwnUser')->middleware('verified');


// Catch all redirects
Route::get('/admin/users/all', fn() => redirect(route('showUsers')));
Route::get('/studio', fn() => redirect(url('dashboard')));
Route::get('/studio/edit-link', fn() => redirect(url('dashboard')));

if(env('ALLOW_USER_EXPORT') != false){
  Route::get('/export-links', [UserController::class, 'exportLinks'])->name('exportLinks');
  Route::get('/export-all', [UserController::class, 'exportAll'])->name('exportAll');
}
if(env('ALLOW_USER_IMPORT') != false){
  Route::post('/import-data', [UserController::class, 'importData'])->name('importData');
}
Route::get('/studio/linkparamform_part/{typeid}/{linkid}', [LinkTypeViewController::class, 'getParamForm'])->name('linkparamform.part');
});
});
}

// Return is authenticated and session-bound, even when the impersonated member is blocked.
Route::post('/auth-as', [AdminController::class, 'authAs'])->middleware('auth')->name('authAs');

//Social login route
Route::get('/social-auth/{provider}/callback', [SocialLoginController::class, 'providerCallback']);
Route::get('/social-auth/{provider}', [SocialLoginController::class, 'redirectToProvider'])->name('social.redirect');
Route::post('/social-auth/{provider}/consent', [SocialLoginController::class, 'consent'])->name('social.consent');

Route::middleware(['auth', 'blocked', 'impersonate'])->group(function () {
//Admin route
Route::group([
    'middleware' => 'admin',
], function () {
    if(env('FORCE_ROUTE_HTTPS') == 'true'){URL::forceScheme('https');}
    Route::get('/panel/index', function(){return redirect(url('dashboard'));});
    Route::get('/admin/overview', [AdminController::class, 'index'])->name('adminOverview');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('showUsers');
    Route::get('/admin/referrals', [ReferralController::class, 'adminIndex'])->name('admin.referrals');
    Route::get('/admin/verification', [VerificationController::class, 'admin'])->name('admin.verification');
    Route::get('/admin/reports', [AbuseReportController::class, 'index'])->name('admin.abuse-reports.index');
    Route::get('/admin/reports/{id}', [AbuseReportController::class, 'show'])->whereNumber('id')->name('admin.abuse-reports.show');
    Route::post('/admin/reports/{id}/status', [AbuseReportController::class, 'update'])->whereNumber('id')->name('admin.abuse-reports.update');
    Route::post('/admin/verification/requests/{id}/review', [VerificationController::class, 'review'])->whereNumber('id')->name('admin.verification.review');
    Route::post('/admin/users/{user}/verification', [VerificationController::class, 'toggle'])->whereNumber('user')->name('admin.verification.toggle');
    Route::post('/admin/users/registration-vip', [AdminController::class, 'updateRegistrationVip'])->name('admin.registration-vip');
    Route::get('/admin/links/{id}', [AdminController::class, 'showLinksUser'])->name('showLinksUser');
    Route::delete('/admin/deleteLink/{id}', [AdminController::class, 'deleteLinkUser'])->name('deleteLinkUser');
    Route::post('/admin/users/block/{block}/{id}', [AdminController::class, 'blockUser'])->name('blockUser');
    Route::post('/admin/users/verify/{verify}/{id}', [AdminController::class, 'verifyCheckUser'])->name('verifyCheckUser');
    Route::post('/admin/users/verify-mail/{verify}/{id}', [AdminController::class, 'verifyUser'])->name('verifyUser');
    Route::get('/admin/edit-user/{id}', [AdminController::class, 'showUser'])->name('showUser');
    Route::post('/admin/edit-user/{id}', [AdminController::class, 'editUser'])->name('editUser');
    Route::post('/admin/edit-user/{id}/media/{kind}', [AdminController::class, 'removeAccountMedia'])->whereNumber('id')->where('kind', 'avatar|background')->name('admin.remove-account-media');
    Route::get('/admin/new-user', [AdminController::class, 'showNewUser'])->name('showNewUser')->middleware('max.users');
    Route::post('/admin/new-user', [AdminController::class, 'createNewUser'])->name('createNewUser')->middleware('max.users');
    Route::delete('/admin/delete-user/{id}', [AdminController::class, 'deleteUser'])->name('deleteUser');
    Route::post('/admin/delete-table-user/{id}', [AdminController::class, 'deleteTableUser'])->name('deleteTableUser');
    Route::get('/admin/pages', [AdminController::class, 'showSitePage'])->name('showSitePage');
    Route::post('/admin/pages', [AdminController::class, 'editSitePage'])->name('editSitePage');
    Route::get('/admin/advanced-config', [AdminController::class, 'showFileEditor'])->name('showFileEditor');
    Route::post('/admin/advanced-config', [AdminController::class, 'editAC'])->name('editAC');
    Route::get('/admin/env', [AdminController::class, 'showFileEditor'])->name('showEnvEditor');
    Route::post('/admin/env', [AdminController::class, 'editENV'])->name('editENV');
    Route::get('/admin/site', [AdminController::class, 'showSite'])->name('showSite');
    Route::post('/admin/site/content', [AdminController::class, 'editPublicSiteContent'])->name('admin.site.content');
    Route::post('/admin/site', [AdminController::class, 'editSite'])->name('editSite');
    Route::post('/admin/site/delavatar', [AdminController::class, 'delAvatar'])->name('delAvatar');
    Route::post('/admin/site/delfavicon', [AdminController::class, 'delFavicon'])->name('delFavicon');
    Route::get('/admin/phpinfo', [AdminController::class, 'phpinfo'])->name('phpinfo');
    Route::get('/admin/backups', [AdminController::class, 'showBackups'])->name('showBackups');
    Route::post('/admin/theme', [AdminController::class, 'deleteTheme'])->name('deleteTheme');
    Route::get('/admin/theme', [AdminController::class, 'showThemes'])->name('showThemes');
    Route::post('/admin/theme-options', [AdminController::class, 'updateThemeOptions'])->name('admin.theme-options');
    Route::get('/admin/github-themes', [\App\Http\Controllers\GithubThemesController::class, 'index'])->name('githubThemes');
    Route::get('/admin/github-themes/status', [\App\Http\Controllers\GithubThemesController::class, 'status'])->name('githubThemes.status');
    Route::post('/admin/github-themes/configure', [\App\Http\Controllers\GithubThemesController::class, 'configure'])->name('githubThemes.configure');
    Route::get('/admin/github-themes/{category}/{name}/thumbnail', [\App\Http\Controllers\GithubThemesController::class, 'thumbnail'])->name('githubThemes.thumbnail');
    Route::post('/admin/github-themes/{category}/{name}/install', [\App\Http\Controllers\GithubThemesController::class, 'install'])->name('githubThemes.install');
    Route::get('/update/theme', [AdminController::class, 'updateThemes'])->name('updateThemes');
    Route::get('/admin/config', [AdminController::class, 'showConfig'])->name('showConfig');
    Route::get('/admin/config/sections/{section}', [AdminController::class, 'showConfigSection'])->where('section', '[a-z-]+')->name('admin.config.section');
    Route::post('/admin/config', [AdminController::class, 'editConfig'])->name('editConfig');
    Route::post('/send-test-email', [AdminController::class, 'SendTestMail'])->name('SendTestMail');
    Route::post('/auth-as/{id}', [AdminController::class, 'authAsID'])->name('authAsID');
    Route::get('/theme-updater', function () {return view('studio/theme-updater', []);});
    // Legacy views execute server updates and backup cleanup from query strings.
    // Keep navigation read-only; operational changes use the controlled VPS procedure.
    Route::get('/update', function () {
        abort_if(request()->server('QUERY_STRING', '') !== '', 403);
        return view('panel.maintenance-operations', ['operation' => 'update']);
    });
    Route::get('/backup', function () {
        abort_if(request()->server('QUERY_STRING', '') !== '', 403);
        return view('panel.maintenance-operations', ['operation' => 'backup']);
    });

    Route::group(['namespace'=>'App\Http\Controllers\Admin', 'prefix'=>'admin', 'as'=>'admin'],function() {
        //Route::resource('/admin/linktype', LinkTypeController::class);
        Route::resources([
            'linktype'=>LinkTypeController::class
        ]);
    });

}); // End Admin authenticated routes
});

// Displays Maintenance Mode page
if(env('MAINTENANCE_MODE') == 'true'){
    Route::any('/{any?}', function () {
        return response()->view('maintenance', [], 503)
            ->header('Retry-After', '300')
            ->header('Cache-Control', 'no-store');
    })->where('any', '.*');
}

require __DIR__.'/auth.php';

if(config('advanced-config.custom_url_prefix') == ""){
  Route::get('/{littlelink}', [UserController::class, 'littlelink'])->name('littlelink.unprefixed');
}
