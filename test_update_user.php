<?php
// Test script: directly invoke User::update to verify save works
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(3);
echo "USER 3 BEFORE: activo=" . var_export($user->auto_llamado_activo, true) . ", minutos=" . $user->auto_llamado_minutos . "\n";

$user->update([
    'auto_llamado_activo' => true,
    'auto_llamado_minutos' => max(1, min(60, intval('2'))),
]);
$user->refresh();
echo "USER 3 AFTER update with minutos=2: activo=" . var_export($user->auto_llamado_activo, true) . ", minutos=" . $user->auto_llamado_minutos . "\n";
