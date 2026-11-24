<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

use GeoSot\EnvEditor\Controllers\EnvController;
use GeoSot\EnvEditor\Exceptions\EnvException;
use GeoSot\EnvEditor\Helpers\EnvFileContentManager;
use GeoSot\EnvEditor\Helpers\EnvFilesManager;
use GeoSot\EnvEditor\Helpers\EnvKeysManager;
use GeoSot\EnvEditor\Facades\EnvEditor;
use GeoSot\EnvEditor\ServiceProvider;

use Auth;
use Exception;
use ZipArchive;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Admin;
use App\Models\Button;
use App\Models\Link;
use App\Models\Page;
use App\Models\UserData;
use App\Services\PublicSiteContent;
use App\Services\RegistrationVip;

class AdminController extends Controller
{
  //Statistics of the number of clicks and links
  public function index()
  {
    $userId = Auth::user()->id;
    $littlelink_name = Auth::user()->littlelink_name;
    $links = Link::where("user_id", $userId)->select("link")->count();
    $clicks = Link::where("user_id", $userId)->sum("click_number");

    $userNumber = User::count();
    $siteLinks = Link::count();
    $siteClicks = Link::sum("click_number");

    $users = User::select(
      "id",
      "name",
      "email",
      "created_at",
      "updated_at",
    )->get();
    $lastMonthCount = $users
      ->where("created_at", ">=", Carbon::now()->subDays(30))
      ->count();
    $lastWeekCount = $users
      ->where("created_at", ">=", Carbon::now()->subDays(7))
      ->count();
    $last24HrsCount = $users
      ->where("created_at", ">=", Carbon::now()->subHours(24))
      ->count();
    $updatedLast30DaysCount = $users
      ->where("updated_at", ">=", Carbon::now()->subDays(30))
      ->count();
    $updatedLast7DaysCount = $users
      ->where("updated_at", ">=", Carbon::now()->subDays(7))
      ->count();
    $updatedLast24HrsCount = $users
      ->where("updated_at", ">=", Carbon::now()->subHours(24))
      ->count();

    $links = Link::where("user_id", $userId)->select("link")->count();
    $clicks = Link::where("user_id", $userId)->sum("click_number");
    $topLinks = Link::where("user_id", $userId)
      ->orderby("click_number", "desc")
      ->whereNotNull("link")
      ->where("link", "<>", "")
      ->take(5)
      ->get();

    $pageStats = [
      "visitors" => [
        "all" => visits("App\Models\User", $littlelink_name)->count(),
        "day" => visits("App\Models\User", $littlelink_name)
          ->period("day")
          ->count(),
        "week" => visits("App\Models\User", $littlelink_name)
          ->period("week")
          ->count(),
        "month" => visits("App\Models\User", $littlelink_name)
          ->period("month")
          ->count(),
        "year" => visits("App\Models\User", $littlelink_name)
          ->period("year")
          ->count(),
      ],
      "os" => visits("App\Models\User", $littlelink_name)->operatingSystems(),
      "referers" => visits("App\Models\User", $littlelink_name)->refs(),
      "countries" => visits("App\Models\User", $littlelink_name)->countries(),
    ];

    $dashboardView = request()->is("admin/overview") ? "panel/admin-overview" : "panel/index";
    return view($dashboardView, [
      "lastMonthCount" => $lastMonthCount,
      "lastWeekCount" => $lastWeekCount,
      "last24HrsCount" => $last24HrsCount,
      "updatedLast30DaysCount" => $updatedLast30DaysCount,
      "updatedLast7DaysCount" => $updatedLast7DaysCount,
      "updatedLast24HrsCount" => $updatedLast24HrsCount,
      "toplinks" => $topLinks,
      "links" => $links,
      "clicks" => $clicks,
      "pageStats" => $pageStats,
      "littlelink_name" => $littlelink_name,
      "links" => $links,
      "clicks" => $clicks,
      "siteLinks" => $siteLinks,
      "siteClicks" => $siteClicks,
      "userNumber" => $userNumber,
    ]);
  }

  // Users page
  public function users()
  {
    return view("panel/users", ['autoVipNewUsers' => RegistrationVip::enabled()]);
  }

  // Administrador decide quais campos estao liberados aos usuarios.
  public function updateThemeOptions(Request $request)
  {
    \App\Services\ThemeOptions::savePermissions($request->all());
    return redirect()->route('showThemes')->with('success', __('messages.lf_theme_saved'));
  }

  // Preferencia global de novos cadastros; administradores apenas (middleware na rota).
  public function updateRegistrationVip(Request $request)
  {
    $request->validate(['enabled' => 'required|boolean']);

    try {
      RegistrationVip::setEnabled($request->boolean('enabled'));
    } catch (\Throwable $exception) {
      report($exception);
      return redirect()->route('showUsers')->with('fail', 'Não foi possível salvar a configuração VIP. Verifique a permissão de escrita no arquivo .env.');
    }

    return redirect()->route('showUsers')->with('success', $request->boolean('enabled')
      ? 'Novos cadastros serão criados como VIP.'
      : 'Novos cadastros voltarão a ser criados como usuários comuns.');
  }

  // Send test mail
  public function SendTestMail(Request $request)
  {
    try {
      $userId = auth()->id();
      $user = User::findOrFail($userId);

      Mail::send("auth.test", ["user" => $user], function ($message) use (
        $user,
      ) {
        $message->to($user->email)->subject("Test Email");
      });

      return redirect()
        ->route("showConfig")
        ->with("success", "Test email sent successfully!");
    } catch (\Exception $e) {
      return redirect()
        ->route("showConfig")
        ->with("fail", "Failed to send test email.");
    }
  }

  //Block user
  public function blockUser(request $request)
  {
    $id = $request->route('id');
    $status = $request->route('block');
    abort_unless(in_array($status, ['yes', 'no'], true), 422);
    abort_if((int) $id === 1 || (int) $id === (int) Auth::id(), 403);
    User::findOrFail($id);

    if ($status == "yes") {
      $block = "no";
    } elseif ($status == "no") {
      $block = "yes";
    }

    User::where("id", $id)->update(["block" => $block]);

    return redirect("admin/users/all");
  }

  //Verify user
  public function verifyCheckUser(request $request)
  {
    $id = $request->route('id');
    $status = $request->route('verify');
    abort_unless(in_array($status, ['vip', 'user'], true), 422);
    abort_if((int) $id === 1 || (int) $id === (int) Auth::id(), 403);
    User::findOrFail($id);

    if ($status == "vip") {
      $verify = "vip";
      UserData::saveData($id, "checkmark", true);
    } elseif ($status == "user") {
      $verify = "user";
    }

    User::where("id", $id)->update(["role" => $verify]);

    return redirect(url("u") . "/" . $id);
  }

  //Verify or un-verify users emails
  public function verifyUser(request $request)
  {
    $id = $request->route('id');
    $status = $request->route('verify');
    abort_unless(in_array($status, ['true', 'false'], true), 422);
    abort_if((int) $id === 1 || (int) $id === (int) Auth::id(), 403);
    User::findOrFail($id);

    if ($status == "true") {
      $verify = now();
    } else {
      $verify = null;
    }

    User::where("id", $id)->update(["email_verified_at" => $verify]);
  }

  //Create new user from the Admin Panel
  public function createNewUser()
  {
    function random_str(
      int $length = 64,
      string $keyspace = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
    ): string {
      if ($length < 1) {
        throw new \RangeException("Length must be a positive integer");
      }
      $pieces = [];
      $max = mb_strlen($keyspace, "8bit") - 1;
      for ($i = 0; $i < $length; ++$i) {
        $pieces[] = $keyspace[random_int(0, $max)];
      }
      return implode("", $pieces);
    }

    $names = User::pluck("name")->toArray();

    $adminCreatedNames = array_filter($names, function ($name) {
      return strpos($name, "Admin-Created-") === 0;
    });

    $numbers = array_map(function ($name) {
      return (int) str_replace("Admin-Created-", "", $name);
    }, $adminCreatedNames);

    $maxNumber = !empty($numbers) ? max($numbers) : 0;
    $newNumber = $maxNumber + 1;

    $domain = parse_url(url(""), PHP_URL_HOST);
    $domain = $domain == "localhost" ? "example.com" : $domain;

    $user = User::create([
      "name" => "Admin-Created-" . $newNumber,
      "email" => strtolower(random_str(8)) . "@" . $domain,
      "password" => Hash::make(random_str(32)),
      "role" => "user",
      "block" => "no",
    ]);

    $user->role = RegistrationVip::roleForNewUser();
    $user->save();

    return redirect("admin/edit-user/" . $user->id);
  }

  //Delete existing user
  public function deleteUser(request $request)
  {
    $this->deleteAccount((int) $request->route('id'));
    return redirect("admin/users/all");
  }

  //Delete existing user with POST request
  public function deleteTableUser(request $request)
  {
    $this->deleteAccount((int) $request->route('id'));
    return response('', 200);
  }

  // Keep foreign-key enforcement active; model deletion hooks retain case history.
  private function deleteAccount(int $id): void
  {
    abort_if($id === 1 || $id === (int) Auth::id(), 403);
    \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
      $user = User::findOrFail($id);
      Link::where("user_id", $id)->delete();
      $user->forceDelete();
    });
  }

  //Show user to edit
  public function showUser(request $request)
  {
    $id = $request->id;

    $data["user"] = User::where("id", $id)->get();

    return view("panel/edit-user", $data);
  }

  //Show link, click number, up link in links page
  public function showLinksUser(request $request)
  {
    $id = $request->id;

    $data["user"] = User::where("id", $id)->get();

    $data["links"] = Link::select(
      "id",
      "link",
      "title",
      "order",
      "click_number",
      "up_link",
      "links.button_id",
    )
      ->where("user_id", $id)
      ->orderBy("up_link", "asc")
      ->orderBy("order", "asc")
      ->paginate(10);
    return view("panel/links", $data);
  }

  //Delete link
  public function deleteLinkUser(request $request)
  {
    $linkId = $request->route('id');

    Link::where("id", $linkId)->delete();

    return back();
  }

  //Save user edit
  public function editUser(request $request)
  {
    $request->validate([
      "name" => "",
      "email" => "",
      "password" => "",
      "littlelink_name" => "",
    ]);

    $id = $request->route('id');
    $name = $request->name;
    $email = $request->email;
    $password = Hash::make($request->password);
    $profilePhoto = $request->file("image");
    $littlelink_name = $request->littlelink_name;
    $littlelink_description = $request->littlelink_description;
    $role = $request->role;
    $customBackground = $request->file("background");
    $theme = $request->theme;

    if (User::where("id", $id)->get("role")->first()->role = !$role) {
      if ($role == "vip") {
        UserData::saveData($id, "checkmark", true);
      }
    }

    if ($request->password == "") {
      User::where("id", $id)->update([
        "name" => $name,
        "email" => $email,
        "littlelink_name" => $littlelink_name,
        "littlelink_description" => $littlelink_description,
        "role" => $role,
        "theme" => $theme,
      ]);
    } else {
      User::where("id", $id)->update([
        "name" => $name,
        "email" => $email,
        "password" => $password,
        "littlelink_name" => $littlelink_name,
        "littlelink_description" => $littlelink_description,
        "role" => $role,
        "theme" => $theme,
      ]);
    }
    if (!empty($profilePhoto)) {
      $profilePhoto->move(base_path("assets/img"), $id . "_" . time() . ".png");
    }
    if (!empty($customBackground)) {
      $directory = base_path("assets/img/background-img/");
      $files = scandir($directory);
      $pathinfo = "error.error";
      foreach ($files as $file) {
        if (strpos($file, $id . ".") !== false) {
          $pathinfo = $id . "." . pathinfo($file, PATHINFO_EXTENSION);
        }
      }
      if (file_exists(base_path("assets/img/background-img/") . $pathinfo)) {
        File::delete(base_path("assets/img/background-img/") . $pathinfo);
      }

      $customBackground->move(
        base_path("assets/img/background-img/"),
        $id . "_" . time() . "." . $request->file("background")->extension(),
      );
    }

    return redirect("admin/users/all");
  }

  //Show site pages to edit
  public function showSitePage()
  {
    $data["pages"] = Page::select(
      "terms",
      "privacy",
      "contact",
      "register",
    )->get();
    return view("panel/pages", $data);
  }

  //Save site pages
  public function editSitePage(request $request)
  {
    $terms = $request->terms;
    $privacy = $request->privacy;
    $contact = $request->contact;
    $register = $request->register;

    Page::first()->update([
      "terms" => $terms,
      "privacy" => $privacy,
      "contact" => $contact,
      "register" => $register,
    ]);

    return back();
  }

  // Editor de paginas publicas em categorias independentes.
  public function showSite(Request $request)
  {
    $sections = PublicSiteContent::sections();
    $selected = (string) $request->query('section', 'identity');
    if ($selected !== 'identity' && !isset($sections[$selected])) {
      abort(404);
    }
    $page = Page::select('home_message')->first();
    return view('panel/site', [
      'home_message' => $page->home_message ?? 'default',
      'siteSections' => $sections,
      'siteSection' => $selected,
      'siteValues' => PublicSiteContent::values(),
    ]);
  }

  // Salva somente o grupo enviado, sem alterar os demais ou os perfis individuais.
  public function editPublicSiteContent(Request $request)
  {
    $section = (string) $request->input('section', '');
    $sections = PublicSiteContent::sections();
    if (!isset($sections[$section])) {
      abort(404);
    }

    $rules = [];
    foreach ($sections[$section]['fields'] as $key => $field) {
      $rules[$key] = ($field['type'] ?? 'text') === 'checkbox'
        ? 'sometimes|boolean'
        : 'nullable|string|max:'.(int) ($field['max'] ?? 250);
    }
    $validated = $request->validate($rules);
    $values = [];
    foreach ($sections[$section]['fields'] as $key => $field) {
      $values[$key] = ($field['type'] ?? 'text') === 'checkbox'
        ? $request->boolean($key)
        : trim((string) ($validated[$key] ?? ''));
    }

    try {
      PublicSiteContent::saveSection($section, $values);
    } catch (\Throwable $exception) {
      report($exception);
      return redirect()->route('showSite', ['section' => $section])
        ->with('fail', 'Não foi possível gravar a seção. Verifique as permissões de storage.');
    }

    return redirect()->route('showSite', ['section' => $section])
      ->with('success', 'Seção atualizada com sucesso.');
  }

  //Save home message, logo and favicon
  public function editSite(request $request)
  {
    $message = $request->message;
    $logo = $request->file("image");
    $icon = $request->file("icon");

    Page::first()->update(["home_message" => $message]);

    if (!empty($logo)) {
      // Delete existing image
      $path = findFile("avatar");
      $path = base_path("/assets/linkstack/images/" . $path);

      // Delete existing image
      if (File::exists($path)) {
        File::delete($path);
      }

      $logo->move(
        base_path("/assets/linkstack/images/"),
        "avatar" . "_" . time() . "." . $request->file("image")->extension(),
      );
    }

    if (!empty($icon)) {
      // Delete existing image
      $path = findFile("favicon");
      $path = base_path("/assets/linkstack/images/" . $path);

      // Delete existing image
      if (File::exists($path)) {
        File::delete($path);
      }

      $icon->move(
        base_path("/assets/linkstack/images/"),
        "favicon" . "_" . time() . "." . $request->file("icon")->extension(),
      );
    }
    return back();
  }

  //Delete avatar
  public function delAvatar()
  {
    $this->removeBrandMedia('avatar');

    return back();
  }

  //Delete favicon
  public function delFavicon()
  {
    $this->removeBrandMedia('favicon');

    return back();
  }

  // Do not remove the bundled fallback favicon.svg or files outside branding.
  private function removeBrandMedia(string $prefix): void
  {
    $path = base_path('assets/linkstack/images');
    $pattern = '/^' . preg_quote($prefix, '/') . '(_\w+)?\.\w+$/iD';
    foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
      if (!$file->isLink() && $file->isFile() &&
          $file->getFilename() !== 'favicon.svg' && preg_match($pattern, $file->getFilename())) {
        abort_unless(File::delete($file->getPathname()), 500, 'Brand media removal failed');
      }
    }
  }

  //View footer page: terms
  public function pagesTerms(Request $request)
  {
    $name = "terms";

    try {
      $data["page"] = Page::select($name)->first();
    } catch (Exception $e) {
      return abort(404);
    }

    return view("pages", ["data" => $data, "name" => $name]);
  }

  //View footer page: privacy
  public function pagesPrivacy(Request $request)
  {
    $name = "privacy";

    try {
      $data["page"] = Page::select($name)->first();
    } catch (Exception $e) {
      return abort(404);
    }

    return view("pages", ["data" => $data, "name" => $name]);
  }

  //View footer page: contact
  public function pagesContact(Request $request)
  {
    $name = "contact";

    try {
      $data["page"] = Page::select($name)->first();
    } catch (Exception $e) {
      return abort(404);
    }

    return view("pages", ["data" => $data, "name" => $name]);
  }

  //Statistics of the number of clicks and links
  public function phpinfo()
  {
    return view("panel/phpinfo");
  }

  //Shows config file editor page
  public function showFileEditor(request $request)
  {
    return redirect("/admin/config");
  }

  //Saves advanced config
  public function editAC(request $request)
  {
    if ($request->ResetAdvancedConfig == "RESET_DEFAULTS") {
      copy(
        base_path("storage/templates/advanced-config.php"),
        base_path("config/advanced-config.php"),
      );
    } else {
      file_put_contents("config/advanced-config.php", $request->AdvancedConfig);
    }

    return redirect("/admin/config#2");
  }

  //Saves .env config
  public function editENV(request $request)
  {
    $config = $request->altConfig;

    file_put_contents(".env", $config);

    return Redirect("/admin/config?alternative-config");
  }

  //Shows config file editor page
  public function showBackups(request $request)
  {
    if ($request->has('file')) {
      $filename = $request->query('file');
      abort_unless(is_string($filename) && preg_match('/^[A-Za-z0-9._-]+$/', $filename), 400);
      $base = realpath(base_path('backups/updater-backups'));
      $path = $base ? realpath($base . DIRECTORY_SEPARATOR . $filename) : false;
      abort_unless($path && is_file($path) && str_starts_with($path, $base . DIRECTORY_SEPARATOR), 404);
      return response()->download($path);
    }
    return view('panel.backups');
  }

  // Delete an installed theme only after validation and confirmation.
  public function deleteTheme(request $request)
  {
    $data = $request->validate([
      'deltheme' => ['required', 'string', 'regex:/^[A-Za-z][A-Za-z0-9_-]*$/D'],
    ]);
    $name = $data['deltheme'];
    $directory = base_path('themes/'.$name);
    if (!is_dir($directory) || is_link($directory)) {
      return back()->withErrors(['theme' => 'Tema não encontrado ou diretório inválido.']);
    }
    if (User::where('theme', $name)->exists()) {
      return back()->withErrors(['theme' => 'Este tema está em uso. Selecione outro tema para os usuários antes de excluir.']);
    }
    try {
      return \Illuminate\Support\Facades\Cache::lock('github-themes-write', 180)->block(5, function () use ($directory, $name) {
        if (!\Illuminate\Support\Facades\File::deleteDirectory($directory)) {
          throw new \RuntimeException('Falha na exclusão.');
        }
        app(\App\Services\GithubThemes::class)->unregister($name);
        return redirect()->route('showThemes')->with('success', 'Tema '.$name.' excluído.');
      });
    } catch (\Throwable $e) {
      return back()->withErrors(['theme' => 'Não foi possível excluir o tema.']);
    }
  }

  // Theme updates are checked exclusively through the configured GitHub repository.
  public function updateThemes()
  {
    return redirect()->route('githubThemes', ['updates' => 1]);
  }

  //Shows config file editor page
  public function showConfig(request $request)
  {
    return view("/panel/config-editor");
  }

  //Shows config file editor page
  public function editConfig(request $request)
  {
    $type = $request->type;
    $entry = $request->entry;
    $value = $request->value;

    if ($type === "toggle") {
      if ($request->toggle != "") {
        $value = "true";
      } else {
        $value = "false";
      }
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    } elseif ($type === "toggle2") {
      if ($request->toggle != "") {
        $value = "verified";
      } else {
        $value = "auth";
      }
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    } elseif ($type === "text") {
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, '"' . $value . '"');
      }
    } elseif ($type === "debug") {
      if ($request->toggle != "") {
        if (EnvEditor::keyExists("APP_DEBUG")) {
          EnvEditor::editKey("APP_DEBUG", "true");
        }
        if (EnvEditor::keyExists("APP_ENV")) {
          EnvEditor::editKey("APP_ENV", "local");
        }
        if (EnvEditor::keyExists("LOG_LEVEL")) {
          EnvEditor::editKey("LOG_LEVEL", "debug");
        }
      } else {
        if (EnvEditor::keyExists("APP_DEBUG")) {
          EnvEditor::editKey("APP_DEBUG", "false");
        }
        if (EnvEditor::keyExists("APP_ENV")) {
          EnvEditor::editKey("APP_ENV", "production");
        }
        if (EnvEditor::keyExists("LOG_LEVEL")) {
          EnvEditor::editKey("LOG_LEVEL", "error");
        }
      }
    } elseif ($type === "register") {
      if ($request->toggle != "") {
        $register = "true";
      } else {
        $register = "false";
      }
      Page::first()->update(["register" => $register]);
    } elseif ($type === "smtp") {
      if ($request->toggle != "") {
        $value = "built-in";
      } else {
        $value = "smtp";
      }
      if (EnvEditor::keyExists("MAIL_MAILER")) {
        EnvEditor::editKey("MAIL_MAILER", $value);
      }

      if (EnvEditor::keyExists("MAIL_HOST")) {
        EnvEditor::editKey("MAIL_HOST", $request->MAIL_HOST);
      }
      if (EnvEditor::keyExists("MAIL_PORT")) {
        EnvEditor::editKey("MAIL_PORT", $request->MAIL_PORT);
      }
      if (EnvEditor::keyExists("MAIL_USERNAME")) {
        EnvEditor::editKey(
          "MAIL_USERNAME",
          '"' . $request->MAIL_USERNAME . '"',
        );
      }
      if (EnvEditor::keyExists("MAIL_PASSWORD")) {
        EnvEditor::editKey(
          "MAIL_PASSWORD",
          '"' . $request->MAIL_PASSWORD . '"',
        );
      }
      if (EnvEditor::keyExists("MAIL_ENCRYPTION")) {
        EnvEditor::editKey("MAIL_ENCRYPTION", $request->MAIL_ENCRYPTION);
      }
      if (EnvEditor::keyExists("MAIL_FROM_ADDRESS")) {
        EnvEditor::editKey("MAIL_FROM_ADDRESS", $request->MAIL_FROM_ADDRESS);
      }
    } elseif ($type === "homeurl") {
      if ($request->value == "default") {
        $value = "";
      } else {
        $value = '"' . $request->value . '"';
      }
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    } elseif ($type === "maintenance") {
      if ($request->toggle != "") {
        $value = "true";
      } else {
        $value = "false";
      }
      if (file_exists(base_path("storage/MAINTENANCE"))) {
        unlink(base_path("storage/MAINTENANCE"));
      }
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    } else {
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    }

    return Redirect("/admin/config");
  }

  //Shows theme editor page
  public function showThemes(request $request)
  {
    return view("/panel/theme");
  }

  //Removes impersonation if authenticated
  public function authAs(Request $request)
  {
    $state = $request->session()->get('linkfree_impersonation');
    abort_unless(is_array($state) && (int) ($state['member_id'] ?? 0) === (int) Auth::id(), 403);
    $admin = User::find($state['admin_id'] ?? null);
    if (!$admin || $admin->role !== 'admin' || $admin->block === 'yes') {
      Auth::guard('web')->logoutCurrentDevice();
      $request->session()->invalidate();
      $request->session()->regenerateToken();
      return redirect('/login');
    }
    Auth::guard('web')->logoutCurrentDevice();
    Auth::guard('web')->login($admin);
    $request->session()->forget('linkfree_impersonation');
    $request->session()->regenerateToken();
    return redirect("admin/users/all");
  }

  //Add impersonation
  public function authAsID(request $request)
  {
    abort_if($request->session()->has('linkfree_impersonation'), 403);
    $admin = Auth::user();
    abort_unless($admin && $admin->role === 'admin' && $admin->block !== 'yes', 403);
    $target = User::findOrFail($request->route('id'));
    abort_unless((int) $target->id !== (int) $admin->id && in_array($target->role, ['user', 'vip'], true) && $target->block !== 'yes', 403);
    abort_unless(env('REGISTER_AUTH') === 'auth' || $target->hasVerifiedEmail(), 403);

    Auth::guard('web')->logoutCurrentDevice();
    Auth::guard('web')->login($target);
    $request->session()->regenerateToken();
    $request->session()->put('linkfree_impersonation', ['admin_id' => (int) $admin->id, 'member_id' => (int) $target->id]);
    return redirect("dashboard");
  }

  //Show info about link
  public function redirectInfo(request $request)
  {
    $linkId = $request->id;

    if (empty($linkId)) {
      return abort(404);
    }

    $linkData = Link::find($linkId);
    $clicks = $linkData->click_number;

    if (empty($linkData)) {
      return abort(404);
    }

    function isValidLink($url)
    {
      $validPrefixes = ["http", "https", "ftp", "mailto", "tel", "news"];

      $pattern = "/^(" . implode("|", $validPrefixes) . "):/i";

      if (preg_match($pattern, $url) && strlen($url) <= 155) {
        return $url;
      } else {
        return "N/A";
      }
    }

    $link = isValidLink($linkData->link);

    $userID = $linkData->user_id;
    $userData = User::find($userID);

    return view("linkinfo", [
      "clicks" => $clicks,
      "linkID" => $linkId,
      "link" => $link,
      "id" => $userID,
      "userData" => $userData,
    ]);
  }
}
