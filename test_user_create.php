<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = \Illuminate\Http\Request::create('/admin/users', 'POST', [
    'name' => 'Test User', 
    'email' => 'test_random_' . time() . '@gmail.com', 
    'contact_number' => '1234567890', 
    'role' => 'Resident', 
    'status' => 'Active', 
    'password' => 'password123', 
    'block' => '1', 
    'lot' => '1'
]); 
$req->headers->set('Accept', 'application/json'); 
$res = app()->handle($req); 
echo $res->getStatusCode() . "\n" . $res->getContent();
