<?php

namespace App\Jobs;

use App\Models\PollingUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePollingUnitsJob implements ShouldQueue
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
            PollingUnit::factory()->count($this->batchSize)->create([
                'remarks' => 'New PU', // or randomize here if desired
                'ward_id' => rand(1, 8809),
            ]);

            // Optional: Log or notify progress here
        }
    }
}
