<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Incident;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PurgeResolvedIncidentMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'incidents:purge-media {--days=60 : Number of days an incident must be resolved before its video is purged}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge heavy video files from resolved incidents older than a specified number of days to keep storage optimal.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $days = 60;
        }

        $cutoff = Carbon::now()->subDays($days);
        $this->info("Scanning for incidents resolved before {$cutoff->toDateTimeString()}...");

        $incidents = Incident::where('status', 'resolved')
            ->where('updated_at', '<=', $cutoff)
            ->whereNotNull('video_url')
            ->get();

        if ($incidents->isEmpty()) {
            $this->info('No old resolved incident videos found to purge.');
            return Command::SUCCESS;
        }

        $purgedCount = 0;
        $bytesFreed = 0;

        foreach ($incidents as $inc) {
            $relativeVideoPath = str_replace('/storage/', '', $inc->video_url);

            if (Storage::disk('public')->exists($relativeVideoPath)) {
                $bytesFreed += Storage::disk('public')->size($relativeVideoPath);
                Storage::disk('public')->delete($relativeVideoPath);
            }

            $inc->update(['video_url' => null]);
            $purgedCount++;
            $this->line("Purged video for INC-{$inc->id} (Resolved on {$inc->updated_at->toDateString()})");
        }

        $mbFreed = round($bytesFreed / (1024 * 1024), 2);
        $this->info("Successfully purged {$purgedCount} video(s), freeing approx. {$mbFreed} MB of storage.");

        return Command::SUCCESS;
    }
}
