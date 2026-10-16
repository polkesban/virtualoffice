<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\AI\RagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;   // 5 minutes — PDF parsing + many embed calls
    public int $tries   = 2;

    public function __construct(private readonly KnowledgeDocument $document) {}

    public function handle(RagService $rag): void
    {
        $rag->indexDocument($this->document);
    }

    public function failed(\Throwable $e): void
    {
        $this->document->update([
            'status'        => 'error',
            'error_message' => $e->getMessage(),
        ]);
    }
}
