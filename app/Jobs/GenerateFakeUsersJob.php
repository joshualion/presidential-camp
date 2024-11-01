<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateFakeUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of polling units to generate.
     *
     * @var int
     */
    protected $totalUnits;
    protected $batchSize;

    /**
     * Create a new job instance.
     */
    public function __construct(int $totalUnits = 176846, int $batchSize = 10000)
    {
        $this->totalUnits = $totalUnits;
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $batches = (int) ceil($this->totalUnits / $this->batchSize);

        for ($i = 0; $i < $batches; $i++) {
            User::factory()->count($this->batchSize)->create([
            ]);

            // Optional: Log or notify progress here
        }
    }
}
