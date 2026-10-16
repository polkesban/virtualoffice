<?php

namespace App\Jobs;

use App\Models\ContentJob;
use App\Services\AI\ContentEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;   // 3 minutes — vision + 2 generation calls
    public int $tries   = 2;

    public function __construct(private readonly ContentJob $contentJob) {}

    public function handle(ContentEngineService $engine): void
    {
        $engine->process($this->contentJob);
    }

    public function failed(\Throwable $e): void
    {
        $this->contentJob->update([
            'status'        => 'error',
            'error_message' => $e->getMessage(),
        ]);
    }
}
