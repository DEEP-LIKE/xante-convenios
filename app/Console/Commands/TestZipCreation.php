<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class TestZipCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:zip-creation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test ZIP file creation with simple names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing ZIP creation...');

        try {
            // Test 1: Simple filename
            $zipFileName = 'test_simple.zip';
            $zipPath = storage_path('app/temp/'.$zipFileName);

            // Create temp directory
            if (! file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive;
            $result = $zip->open($zipPath, ZipArchive::CREATE);

            $this->info('ZIP creation result: '.$result);

            if ($result === true) {
                // Add a simple text file
                $zip->addFromString('test.txt', 'This is a test file');
                $zip->close();
                $this->info('✅ Simple ZIP created successfully: '.$zipPath);

                // Clean up
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }
            } else {
                $this->error('❌ Failed to create simple ZIP. Error code: '.$result);
            }

            // Test 2: Complex filename
            $complexName = 'convenio_19_1698765432.zip';
            $zipPath2 = storage_path('app/temp/'.$complexName);

            $zip2 = new ZipArchive;
            $result2 = $zip2->open($zipPath2, ZipArchive::CREATE);

            if ($result2 === true) {
                $zip2->addFromString('generados/test.pdf', 'Test PDF content');
                $zip2->addFromString('cliente/test.jpg', 'Test JPG content');
                $zip2->close();
                $this->info('✅ Complex ZIP created successfully: '.$zipPath2);

                // Clean up
                if (file_exists($zipPath2)) {
                    unlink($zipPath2);
                }
            } else {
                $this->error('❌ Failed to create complex ZIP. Error code: '.$result2);
            }

        } catch (\Exception $e) {
            $this->error('Exception: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());
        }

        return 0;
    }
}
