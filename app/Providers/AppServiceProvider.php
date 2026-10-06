<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('layouts.admin', function ($view) {
            $pendingVisitors = \Illuminate\Support\Facades\Schema::hasTable('visitors') ? \Illuminate\Support\Facades\DB::table('visitors')->where('status', 'Pending')->count() : 0;
            $pendingAppointments = \Illuminate\Support\Facades\Schema::hasTable('appointments') ? \Illuminate\Support\Facades\DB::table('appointments')->where('status', 'Pending')->count() : 0;
            $pendingIncidents = \Illuminate\Support\Facades\Schema::hasTable('incidents') ? \Illuminate\Support\Facades\DB::table('incidents')->where('status', 'pending')->count() : 0;
            
            $notifications = [];
            $unreadCount = 0;
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                $notifications = $user->notifications()->take(10)->get();
                $unreadCount = $user->unreadNotifications()->count();
            }

            $view->with('sidebarCounts', [
                'visitors' => $pendingVisitors,
                'appointments' => $pendingAppointments,
                'incidents' => $pendingIncidents,
            ])->with('adminNotifications', collect($notifications))->with('adminUnreadCount', $unreadCount);
        });
    }
}
