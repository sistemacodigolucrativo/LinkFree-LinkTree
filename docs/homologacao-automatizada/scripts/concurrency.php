<?php
$base=dirname(__DIR__,3).'/linkstack';if(realpath($base)!=='/tmp/linkfree-audit/linkstack')exit(2);chdir($base);require 'vendor/autoload.php';$app=require 'bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(config('database.connections.sqlite.database')!==$base.'/database/audit.sqlite')exit(3);
$user=App\Models\User::where('email','auditother@example.test')->firstOrFail();
while(microtime(true)<(float)$argv[1])usleep(1000);
try{echo json_encode(['code'=>App\Services\ReferralService::ensureCode($user)]);}catch(Throwable $e){echo json_encode(['error'=>get_class($e)]);exit(1);}
