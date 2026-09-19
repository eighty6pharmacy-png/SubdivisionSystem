<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

try {
    DB::statement('TRUNCATE utility_bills, payments, reservations, downpayments, user_lots, incidents, visitors, announcements, appointments CASCADE;');

    // Delete all users except Admin
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
    if ($adminRole) {
        $adminIds = DB::table('model_has_roles')
            ->where('role_id', $adminRole->id)
            ->pluck('model_id')
            ->toArray();
        
        DB::table('users')->whereNotIn('id', $adminIds)->delete();
    } else {
        $admin = User::first(); // Keep at least one user
        if ($admin) {
            DB::table('users')->where('id', '!=', $admin->id)->delete();
        }
    }

    DB::table('lots')->update(['status' => 'Available', 'provider_managed' => 1]);
    
    echo "Database cleaned successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
