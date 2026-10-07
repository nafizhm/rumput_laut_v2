<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$migration = require 'database/migrations/2026_10_06_225852_create_invoice_payments_table.php';
$migration->up();
echo "first ok\n";
$migration->up();
echo "second ok\n";
