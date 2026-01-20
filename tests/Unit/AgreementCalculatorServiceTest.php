<?php

namespace Tests\Unit;

use App\Models\ConfigurationCalculator;
use App\Services\AgreementCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgreementCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AgreementCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AgreementCalculatorService;

        // Crear configuraciones de prueba
        $this->createTestConfigurations();
    }

    protected function createTestConfigurations(): void
    {
        ConfigurationCalculator::updateOrCreate(
            ['key' => 'comision_sin_iva_default'],
            [
                'name' => 'Comisión Sin IVA Default',
                'value' => '6.50',
                'type' => 'decimal',
                'group' => 'calculator',
            ]
        );

        ConfigurationCalculator::updateOrCreate(
            ['key' => 'comision_iva_incluido_default'],
            [
                'name' => 'IVA Default',
                'value' => '16.00',
                'type' => 'decimal',
                'group' => 'calculator',
            ]
        );

        ConfigurationCalculator::updateOrCreate(
            ['key' => 'precio_promocion_multiplicador_default'],
            [
                'name' => 'Multiplicador Precio Promoción Default',
                'value' => '1.09',
                'type' => 'decimal',
                'group' => 'calculator',
            ]
        );

        ConfigurationCalculator::updateOrCreate(
            ['key' => 'isr_default'],
            [
                'name' => 'ISR Default',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'calculator',
            ]
        );

        ConfigurationCalculator::updateOrCreate(
            ['key' => 'cancelacion_hipoteca_default'],
            [
                'name' => 'Cancelación Hipoteca Default',
                'value' => '20000',
                'type' => 'decimal',
                'group' => 'calculator',
            ]
        );

        ConfigurationCalculator::updateOrCreate(
            ['key' => 'monto_credito_default'],
            [
                'name' => 'Monto Crédito Default',
                'value' => '800000',
                'type' => 'decimal',
                'group' => 'calculator',
            ]
        );
    }

    #[Test]
    public function it_gets_default_configuration()
    {
        $config = $this->service->getDefaultConfiguration();

        $this->assertIsArray($config);
        $this->assertEquals(6.50, $config['porcentaje_comision_sin_iva']);
        $this->assertEquals(16.00, $config['iva_percentage']);
        $this->assertEquals(1.09, $config['precio_promocion_multiplicador']);
        $this->assertEquals(0, $config['isr']);
        $this->assertEquals(20000, $config['cancelacion_hipoteca']);
        $this->assertEquals(800000, $config['monto_credito']);
    }

    #[Test]
    public function it_calculates_all_financials_correctly()
    {
        $valorConvenio = 1000000; // 1 millón
        $calculations = $this->service->calculateAllFinancials($valorConvenio);

        // Verificar cálculos básicos
        $this->assertEquals(1090000, $calculations['precio_promocion']); // 1,000,000 * 1.09
        $this->assertEquals(1000000, $calculations['valor_compraventa']); // Espejo del valor convenio
        $this->assertEquals(65000, $calculations['monto_comision_sin_iva']); // 1,000,000 * 6.5%
        $this->assertEquals(75400, $calculations['comision_total_pagar']); // 1,000,000 * 7.54%
        $this->assertEquals(20000, $calculations['total_gastos_fi_venta']); // ISR (0) + Cancelación (20,000)

        // Ganancia Final = Valor CompraVenta - ISR - Cancelación - Comisión Total - Monto Crédito
        // 1,000,000 - 0 - 20,000 - 75,400 - 800,000 = 104,600
        $this->assertEquals(104600, $calculations['ganancia_final']);
    }

    #[Test]
    public function it_calculates_with_custom_parameters()
    {
        $valorConvenio = 500000;
        $customParams = [
            'porcentaje_comision_sin_iva' => 5.0,
            'iva_percentage' => 20.0, // 5 * 1.20 = 6%
            'isr' => 10000,
            'cancelacion_hipoteca' => 15000,
            'monto_credito' => 400000,
        ];

        $calculations = $this->service->calculateAllFinancials($valorConvenio, $customParams);

        $this->assertEquals(25000, $calculations['monto_comision_sin_iva']); // 500,000 * 5%
        $this->assertEquals(30000, $calculations['comision_total_pagar']); // 500,000 * 6%
        $this->assertEquals(25000, $calculations['total_gastos_fi_venta']); // 10,000 + 15,000

        // Ganancia Final = 500,000 - 10,000 - 15,000 - 30,000 - 400,000 = 45,000
        $this->assertEquals(45000, $calculations['ganancia_final']);
    }

    #[Test]
    public function it_returns_empty_calculation_for_zero_value()
    {
        $calculations = $this->service->calculateAllFinancials(0);
        $emptyCalculation = $this->service->getEmptyCalculation();

        $this->assertEquals($emptyCalculation, $calculations);
        $this->assertEquals(0, $calculations['precio_promocion']);
        $this->assertEquals(0, $calculations['ganancia_final']);
    }

    #[Test]
    public function it_formats_calculations_for_ui()
    {
        $calculations = [
            'precio_promocion' => 1090000,
            'valor_compraventa' => 1000000,
            'monto_comision_sin_iva' => 65000.50,
            'comision_total_pagar' => 75400.75,
            'total_gastos_fi_venta' => 20000,
            'ganancia_final' => 104599.25,
        ];

        $formatted = $this->service->formatCalculationsForUI($calculations);

        $this->assertEquals('1,090,000', $formatted['precio_promocion']);
        $this->assertEquals('1,000,000.00', $formatted['valor_compraventa']);
        $this->assertEquals('65,000.50', $formatted['monto_comision_sin_iva']);
        $this->assertEquals('75,400.75', $formatted['comision_total_pagar']);
        $this->assertEquals('20,000.00', $formatted['total_gastos_fi_venta']);
        $this->assertEquals('104,599.25', $formatted['ganancia_final']);
    }

    #[Test]
    public function it_validates_parameters_correctly()
    {
        // Valor convenio negativo
        $errors = $this->service->validateParameters(-100);
        $this->assertContains('El valor del convenio no puede ser negativo', $errors);

        // Valor convenio muy alto
        $errors = $this->service->validateParameters(9999999999);
        $this->assertContains('El valor del convenio es demasiado alto', $errors);

        // Porcentaje de IVA inválido
        $errors = $this->service->validateParameters(100000, ['iva_percentage' => 150]);
        $this->assertContains('El porcentaje de comisión con IVA debe estar entre 0 y 100', $errors);

        // Multiplicador inválido
        $errors = $this->service->validateParameters(100000, ['precio_promocion_multiplicador' => 0]);
        $this->assertContains('El multiplicador de precio promoción debe estar entre 0.01 y 10', $errors);

        // Valores válidos
        $errors = $this->service->validateParameters(100000, [
            'porcentaje_comision_sin_iva' => 6.5,
            'iva_percentage' => 16.00,
            'precio_promocion_multiplicador' => 1.09,
        ]);
        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_calculates_financial_summary()
    {
        $calculations = [
            'parametros_utilizados' => ['valor_convenio' => 1000000],
            'ganancia_final' => 104600,
            'comision_total_pagar' => 75400,
        ];

        $summary = $this->service->getFinancialSummary($calculations);

        $this->assertEquals('$1,000,000.00', $summary['valor_convenio_formatted']);
        $this->assertEquals('$104,600.00', $summary['ganancia_final_formatted']);
        $this->assertEquals('$75,400.00', $summary['comision_total_formatted']);
        $this->assertEquals(10.46, $summary['porcentaje_ganancia']); // (104,600 / 1,000,000) * 100
        $this->assertTrue($summary['es_rentable']);
    }

    #[Test]
    public function it_identifies_non_profitable_proposals()
    {
        $calculations = [
            'parametros_utilizados' => ['valor_convenio' => 100000],
            'ganancia_final' => -5000, // Pérdida
            'comision_total_pagar' => 7540,
        ];

        $summary = $this->service->getFinancialSummary($calculations);

        $this->assertEquals(-5.0, $summary['porcentaje_ganancia']);
        $this->assertFalse($summary['es_rentable']);
    }

    #[Test]
    public function it_handles_edge_cases_in_calculations()
    {
        // Caso: Valor convenio muy pequeño
        $calculations = $this->service->calculateAllFinancials(1);
        $this->assertEquals(1, $calculations['precio_promocion']); // Redondeado
        $this->assertEquals(1, $calculations['valor_compraventa']);

        // Caso: Parámetros en cero
        $calculations = $this->service->calculateAllFinancials(100000, [
            'porcentaje_comision_sin_iva' => 0,
            'iva_percentage' => 0,
            'isr' => 0,
            'cancelacion_hipoteca' => 0,
            'monto_credito' => 0,
        ]);

        $this->assertEquals(0, $calculations['monto_comision_sin_iva']);
        $this->assertEquals(0, $calculations['comision_total_pagar']);
        $this->assertEquals(100000, $calculations['ganancia_final']); // Solo el valor convenio
    }

    #[Test]
    public function it_maintains_precision_in_calculations()
    {
        $valorConvenio = 123456.78;
        $calculations = $this->service->calculateAllFinancials($valorConvenio);

        // Verificar que los cálculos mantienen precisión decimal
        $expectedComision = round(($valorConvenio * 6.50) / 100, 2);
        $this->assertEquals($expectedComision, $calculations['monto_comision_sin_iva']);

        $expectedComisionTotal = round(($valorConvenio * 7.54) / 100, 2);
        $this->assertEquals($expectedComisionTotal, $calculations['comision_total_pagar']);
    }
}
