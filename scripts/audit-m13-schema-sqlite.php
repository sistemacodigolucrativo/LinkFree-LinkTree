<?php
declare(strict_types=1);
// M13 smoke using a temporary SQLite only; no VPS access.
$base=dirname(__DIR__).'/linkstack';
chdir($base);
require $base.'/vendor/autoload.php';
$app=require $base.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=tempnam(sys_get_temp_dir(), 'lf-m13-');
if ($db===false) { throw new RuntimeException('Temp SQLite unavailable'); }
register_shutdown_function(static function() use($db):void { @unlink($db); });
try {
    config([
      'database.default'=>'sqlite',
      'database.connections.sqlite.database'=>$db,
      'database.connections.sqlite.foreign_key_constraints'=>true,
      'cache.default'=>'array','session.driver'=>'array',
    ]);
    \Illuminate\Support\Facades\DB::purge('sqlite');
    $schema=\Illuminate\Support\Facades\Schema::connection('sqlite');
    $schema->create('users',static function($table) {
      $table->bigIncrements('id');
      $table->string('name');
      $table->string('email');
      $table->string('role')->default('user');
      $table->string('block')->default('no');
      $table->timestamps();
    });
    foreach ([
      '2026_11_23_000002_create_referral_tables.php',
      '2026_11_23_000003_create_profile_verification_workflow.php',
      '2026_11_23_000004_create_abuse_reports_tables.php',
    ] as $path) {
      $migration=require $base.'/database/migrations/'.$path;
      $migration->up();
      echo "PASS migration $path\n";
    }
    foreach ([
      'referral_codes','referrals','verification_requests',
      'verification_audit_events','abuse_reports','abuse_report_events'
    ] as $table) {
      if (!$schema->hasTable($table)) { throw new RuntimeException('Missing table '.$table); }
    }
    foreach(['verified_at','verification_visible'] as $column) {
      if (!$schema->hasColumn('users',$column)) { throw new RuntimeException('Missing users.'.$column); }
    }
    $conn=\Illuminate\Support\Facades\DB::connection('sqlite');
    $conn->table('users')->insert([
      ['id'=>1,'name'=>'Inviter','email'=>'one@example.test','created_at'=>now(),'updated_at'=>now()],
      ['id'=>2,'name'=>'Invited','email'=>'two@example.test','created_at'=>now(),'updated_at'=>now()]
    ]);
    $conn->table('referral_codes')->insert([
      'user_id'=>1,'code'=>str_repeat('a',24),'created_at'=>now(),'updated_at'=>now()
    ]);
    $conn->table('referrals')->insert([
      'referrer_user_id'=>1,'invited_user_id'=>2,'referral_code'=>str_repeat('a',24),
      'created_at'=>now(),'updated_at'=>now()
    ]);
    $conn->table('verification_requests')->insert([
      'user_id'=>2,'status'=>'pending','requested_at'=>now(),
      'created_at'=>now(),'updated_at'=>now()
    ]);
    $report=$conn->table('abuse_reports')->insertGetId([
      'reported_user_id'=>2,'reporter_user_id'=>1,
      'reported_url'=>'https://example.test/a','category'=>'fraud_scams',
      'status'=>'new','created_at'=>now(),'updated_at'=>now()
    ]);
    $conn->table('abuse_report_events')->insert([
      'abuse_report_id'=>$report,'actor_user_id'=>1,
      'action'=>'created','to_status'=>'new','created_at'=>now()
    ]);
    if (count($conn->select('PRAGMA foreign_key_check'))>0) {
      throw new RuntimeException('FK violations');
    }
    if ($conn->selectOne('PRAGMA quick_check')->quick_check!=='ok') {
      throw new RuntimeException('SQLite integrity failed');
    }
    echo "PASS: additive M13 migrations and writes in isolated SQLite\n";
} catch(\Throwable $e) {
    fwrite(STDERR,"FAIL M13 SCHEMA: ".$e->getMessage()."\n");
    exit(1);
}
