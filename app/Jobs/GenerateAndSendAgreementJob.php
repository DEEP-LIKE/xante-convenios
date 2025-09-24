<?php

namespace App\Jobs;

use App\Mail\AgreementMail;
use App\Models\Agreement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateAndSendAgreementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Agreement $agreement
    ) {}

    public function handle(): void
    {
        try {
            // Load relationships
            $this->agreement->load(['client', 'property', 'calculation']);

            // Generate PDF
            $pdf = Pdf::loadView('pdfs.convenio', ['agreement' => $this->agreement]);
            
            // Create filename
            $filename = 'convenio_' . str_pad($this->agreement->id, 6, '0', STR_PAD_LEFT) . '_' . now()->format('Y-m-d') . '.pdf';
            
            // Save PDF to storage
            $pdfPath = 'agreements/' . $filename;
            Storage::disk('public')->put($pdfPath, $pdf->output());
            
            // Update agreement with PDF path
            $this->agreement->update([
                'pdf_path' => $pdfPath,
                'status' => 'pendiente_docs'
            ]);

            // Send email with PDF attachment
            Mail::to($this->agreement->client->email)
                ->send(new AgreementMail($this->agreement, Storage::disk('public')->path($pdfPath)));

            // Update sent timestamp
            $this->agreement->update([
                'sent_at' => now(),
                'status' => 'completado'
            ]);

        } catch (\Exception $e) {
            // Log error and optionally notify administrators
            \Log::error('Failed to generate and send agreement', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }
}
