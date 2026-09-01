<?php

namespace Tests\Unit;

use App\Models\Agreement;
use App\Models\Client;
use App\Services\PdfGenerationService;
use Tests\TestCase;

class PdfNumberConversionTest extends TestCase
{
    protected PdfGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PdfGenerationService();
    }

    public function test_percentage_to_words_handles_floats_with_floating_point_imprecision(): void
    {
        // 3.5 con residuo infinitesimal típico de IEEE 754
        $floatWithImprecision = (45360 / 1296000) * 100; // 3.5
        $words = $this->service->percentageToWords($floatWithImprecision);
        
        $this->assertEquals('tres punto cinco por ciento', $words);
        $this->assertStringNotContainsString('cero cero', $words);
        $this->assertStringNotContainsString('cuatro', $words);
    }

    public function test_percentage_to_words_various_values(): void
    {
        $this->assertEquals('tres punto cinco por ciento', $this->service->percentageToWords(3.5));
        $this->assertEquals('seis punto cinco por ciento', $this->service->percentageToWords(6.5));
        $this->assertEquals('cinco por ciento', $this->service->percentageToWords(5.0));
        $this->assertEquals('tres punto setenta y cinco por ciento', $this->service->percentageToWords(3.75));
        $this->assertEquals('cero punto cinco por ciento', $this->service->percentageToWords(0.5));
        $this->assertEquals('cero por ciento', $this->service->percentageToWords(0));
    }

    public function test_number_to_words_for_promotional_prices(): void
    {
        $this->assertEquals('un millón doscientos noventa y seis mil', $this->service->numberToWords(1296000.00));
        $this->assertEquals('quinientos mil', $this->service->numberToWords(500000));
        $this->assertEquals('dos millones quinientos mil', $this->service->numberToWords(2500000.00));
    }

    public function test_prepare_template_data_sanitizes_commission_and_price(): void
    {
        $agreement = new Agreement();
        $client = new Client();
        $client->name = 'GUILLERMO LOPEZ BLANCO';
        $agreement->setRelation('client', $client);

        $agreement->wizard_data = [
            'holder_name' => 'GUILLERMO LOPEZ BLANCO',
            'valor_convenio' => '1,296,000.00',
            'monto_comision_sin_iva' => '45,360.00', // equivale a 3.5%
            'precio_promocion' => '1,296,000.00',
            'porcentaje_comision_sin_iva' => '3.5',
            'domicilio_convenio' => 'Privada Real Castilla',
            'numero_interior' => '',
        ];

        $data = $this->service->prepareTemplateData($agreement);

        $this->assertEquals('3.5', $data['porcentaje_comision']);
        $this->assertEquals('tres punto cinco por ciento', $data['porcentaje_comision_letras']);
        $this->assertEquals('un millón doscientos noventa y seis mil', $data['precio_promocion_letras']);
        $this->assertEquals(1296000.0, $data['precio_promocion']);
    }
}
