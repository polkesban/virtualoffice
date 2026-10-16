<?php

namespace App\Console\Commands;

use App\Jobs\IndexDocumentJob;
use App\Models\KnowledgeDocument;
use Illuminate\Console\Command;

class IndexKnowledge extends Command
{
    protected $signature = 'knowledge:index
                            {--id= : Re-index a specific document by ID}
                            {--force : Re-index even if already indexed}
                            {--sync : Run synchronously (no queue)}';

    protected $description = 'Index knowledge base documents for the RAG concierge';

    public function handle(): int
    {
        $query = KnowledgeDocument::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif (! $this->option('force')) {
            $query->whereNotIn('status', ['indexed', 'indexing']);
        }

        $docs = $query->get();

        if ($docs->isEmpty()) {
            $this->info('No documents need indexing. Use --force to re-index all.');
            return self::SUCCESS;
        }

        $this->info("Queuing {$docs->count()} document(s) for indexing...");
        $bar = $this->output->createProgressBar($docs->count());

        foreach ($docs as $doc) {
            if ($this->option('sync')) {
                app(\App\Services\AI\RagService::class)->indexDocument($doc);
            } else {
                IndexDocumentJob::dispatch($doc);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done. Run `php artisan queue:work` to process queued jobs.');

        return self::SUCCESS;
    }
}
