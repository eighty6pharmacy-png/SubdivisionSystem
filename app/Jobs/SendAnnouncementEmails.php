<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Announcement;
use App\Models\User;
use App\Mail\AnnouncementPosted;
use Illuminate\Support\Facades\Mail;

class SendAnnouncementEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $announcement;

    /**
     * Create a new job instance.
     */
    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $residents = User::role('Resident')->get();
        foreach ($residents as $resident) {
            Mail::to($resident->email)->send(new AnnouncementPosted($this->announcement));
        }
    }
}
