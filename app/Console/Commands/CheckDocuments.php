<?php

namespace App\Console\Commands;

use App\Models\ClientDocument;
use Illuminate\Console\Command;

class CheckDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:documents {agreement_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check client documents for an agreement';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agreementId = $this->argument('agreement_id');

        $docs = ClientDocument::where('agreement_id', $agreementId)->get();

        $this->info("Documents for agreement {$agreementId}:");
        $this->info('Total: '.$docs->count());

        foreach ($docs as $doc) {
            $this->line("ID: {$doc->id}");
            $this->line("Name: '{$doc->document_name}'");
            $this->line("Type: '{$doc->document_type}'");
            $this->line("File: '{$doc->file_name}'");
            $this->line("Category: '{$doc->category}'");
            $this->line('---');
        }

        return 0;
    }
}
