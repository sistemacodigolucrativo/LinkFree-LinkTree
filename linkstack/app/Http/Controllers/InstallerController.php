<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class InstallerController extends Controller
{
    private function fresh(): void
    {
        abort_unless(is_file(base_path('INSTALLING')) && !is_file(base_path('INSTALLERLOCK')), 403);
        abort_if(Schema::hasTable('users') && User::exists(), 403);
    }

    // Serialize installer writes; no GET path can change markers or database.
    private function serialized(callable $operation)
    {
        $lock = fopen(storage_path('app/installer-operation.lock'), 'c');
        abort_unless($lock !== false, 503);
        try {
            abort_unless(flock($lock, LOCK_EX | LOCK_NB), 409);
            return $operation();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function owner(Request $request): User
    {
        abort_unless(is_file(base_path('INSTALLING')) && is_file(base_path('INSTALLERLOCK')), 403);
        $user = User::find(1);
        abort_unless($user && $user->role === 'admin' &&
            $request->session()->get('lf_setup_admin') === hash('sha256', $user->password), 403);
        return $user;
    }

    // Update the selected keys atomically, preserving unrelated settings.
    private function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);
        abort_unless($content !== false, 503);
        foreach ($values as $key => $value) {
            $value = (string) $value;
            abort_if(preg_match('/[\x00-\x1f\x7f]/', $value), 422);
            $quoted = '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value) . '"';
            $pattern = '/^' . preg_quote($key, '/') . '[ \t]*=.*$/m';
            $content = preg_replace_callback($pattern, fn () => $key . '=' . $quoted, $content, -1, $count);
            if (!$count) {
                $content .= "\n" . $key . '=' . $quoted . "\n";
            }
        }
        $parsed = \Dotenv\Dotenv::parse($content);
        foreach ($values as $key => $value) {
            abort_unless(($parsed[$key] ?? null) === (string) $value, 422, 'Invalid configuration value');
        }
        $tmp = tempnam(dirname($path), '.lf-setup-');
        abort_unless($tmp !== false, 503);
        try {
            chmod($tmp, fileperms($path) & 0777);
            abort_unless(file_put_contents($tmp, $content) === strlen($content), 503);
            abort_unless(rename($tmp, $path), 503);
        } finally {
            if (is_file($tmp)) unlink($tmp);
        }
    }

    private function initializeDatabase(): void
    {
        abort_unless(Artisan::call('migrate', ['--force'=>true, '--no-interaction'=>true]) === 0, 503);
        abort_if(User::exists(), 403);
        if (!DB::table('pages')->exists()) {
            abort_unless(Artisan::call('db:seed', ['--class'=>'PageSeeder','--force'=>true]) === 0, 503);
        }
        if (!DB::table('buttons')->exists()) {
            abort_unless(Artisan::call('db:seed', ['--class'=>'ButtonSeeder','--force'=>true]) === 0, 503);
        }
    }

    public function showInstaller(Request $request)
    {
        if (is_file(base_path('INSTALLERLOCK'))) {
            $this->owner($request);
            if ($request->server('QUERY_STRING', '') !== '5') return redirect(url('?5'));
        } else {
            $this->fresh();
            abort_if($request->server('QUERY_STRING', '') === '5', 403);
        }
        return view('installer/installer');
    }

    public function db(Request $request)
    {
        return $this->serialized(function () use ($request) {
            $this->fresh();
            $data = $request->validate(['database'=>'required|in:SQLite,MySQL']);
            if ($data['database'] === 'MySQL') return redirect(url('?mysql'));
            abort_unless(config('database.default') === 'sqlite', 409, 'Database already configured');
            $this->initializeDatabase();
            return redirect(url('?4'));
        });
    }

    public function createAdmin(Request $request)
    {
        return $this->serialized(function () use ($request) {
            $this->fresh();
            $data = $request->validate([
                'email'=>'required|email|max:255',
                'password'=>'required|string|min:8|max:255',
                'handle'=>'required|string|max:100|regex:/^[A-Za-z0-9_-]+$/D',
                'name'=>'required|string|max:255',
            ]);
            abort_unless(Schema::hasTable('users') && Schema::hasTable('pages') && DB::table('pages')->exists() && DB::table('buttons')->exists(), 409);
            $user = DB::transaction(function () use ($data) {
                abort_if(User::exists(), 403);
                $user = new User;
                $user->forceFill([
                    'id'=>1, 'name'=>$data['name'], 'email'=>$data['email'],
                    'password'=>Hash::make($data['password']), 'email_verified_at'=>now(),
                    'littlelink_name'=>$data['handle'], 'littlelink_description'=>'admin page',
                    'role'=>'admin', 'block'=>'no',
                ]);
                $user->save();
                return $user;
            });
            abort_unless(touch(base_path('INSTALLERLOCK')), 503);
            $request->session()->regenerate();
            $request->session()->put('lf_setup_admin', hash('sha256', $user->password));
            return redirect(url('?5'));
        });
    }

    public function mysql(Request $request)
    {
        return $this->serialized(function () use ($request) {
            $this->fresh();
            abort_unless(config('database.default') === 'sqlite', 409, 'Database already configured');
            $data = $request->validate([
                'host'=>'required|string|max:255|regex:/^[A-Za-z0-9_.:-]+$/D',
                'port'=>'required|integer|min:1|max:65535',
                'name'=>'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/D',
                'username'=>'required|string|max:128|regex:/^[A-Za-z0-9_.-]+$/D',
                'password'=>'nullable|string|max:255',
            ]);
            $password = (string) ($data['password'] ?? '');
            abort_if(preg_match('/[\x00-\x1f\x7f]/', $password), 422);
            config(['database.connections.mysql.url'=>null, 'database.connections.mysql.unix_socket'=>'',
                'database.connections.mysql.host'=>$data['host'], 'database.connections.mysql.port'=>$data['port'],
                'database.connections.mysql.database'=>$data['name'], 'database.connections.mysql.username'=>$data['username'],
                'database.connections.mysql.password'=>$password]);
            DB::purge('mysql');
            try {
                // Refuse existing schemas before any migration, seed or env write.
                abort_if(count(DB::connection('mysql')->select('SHOW TABLES')) !== 0, 409, 'Use an empty dedicated database');
                DB::setDefaultConnection('mysql');
                $this->initializeDatabase();
                $this->writeEnv(['DB_CONNECTION'=>'mysql','DB_HOST'=>$data['host'],'DB_PORT'=>$data['port'],
                    'DB_DATABASE'=>$data['name'],'DB_USERNAME'=>$data['username'],'DB_PASSWORD'=>$password,
                    'DATABASE_URL'=>'','DB_SOCKET'=>'']);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Never flash or print credentials. A failed partial schema requires operator recovery.
                return redirect(url('?error'));
            } finally {
                DB::purge('mysql');
                DB::setDefaultConnection('sqlite');
            }
            return redirect(url('?4'));
        });
    }

    public function options(Request $request)
    {
        return $this->serialized(function () use ($request) {
            $user = $this->owner($request);
            $data = $request->validate([
                'register'=>'required|in:Yes,No','verify'=>'required|in:Yes,No','page'=>'required|in:Yes,No',
                'app'=>'required|string|max:100',
            ]);
            $this->writeEnv(['ALLOW_REGISTRATION'=>$data['register']==='Yes'?'true':'false',
                'REGISTER_AUTH'=>$data['verify']==='Yes'?'verified':'auth',
                'HOME_URL'=>$data['page']==='Yes'?$user->littlelink_name:'',
                'APP_NAME'=>$data['app'],'ADMIN_EMAIL'=>$user->email]);
            abort_unless(unlink(base_path('INSTALLING')), 503);
            abort_unless(unlink(base_path('INSTALLERLOCK')), 503);
            $request->session()->forget('lf_setup_admin');
            $request->session()->regenerate();
            return redirect(url('dashboard'));
        });
    }

    public function editConfigInstaller(Request $request)
    {
        return $this->serialized(function () use ($request) {
            $this->fresh();
            $data = $request->validate(['entry'=>'required|in:LOCALE','value'=>'required|string|regex:/^[A-Za-z0-9_-]+$/D']);
            abort_unless(is_dir(resource_path('lang/'.$data['value'])), 422);
            $this->writeEnv(['LOCALE'=>$data['value']]);
            return redirect(url('/'));
        });
    }
}
