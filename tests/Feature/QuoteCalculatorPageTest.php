<?php

namespace Tests\Feature;

use App\Filament\Pages\QuoteCalculatorPage;
use App\Models\Client;
use App\Models\ConfigurationCalculator;
use App\Models\Proposal;
use App\Models\StateCommissionRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuoteCalculatorPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@xante.com',
        ]);
        $this->client = Client::factory()->create([
            'name' => 'Cliente Test',
            'xante_id' => 'TEST001',
            'email' => 'cliente@test.com',
        ]);

        StateCommissionRate::create([
            'state_name' => 'CDMX',
            'state_code' => 'CDMX',
            'commission_percentage' => 5.0,
            'is_active' => true,
        ]);

        $this->createTestConfigurations();
        $this->actingAs($this->user);
    }

    protected function createTestConfigurations(): void
    {
        $configs = [
            'comision_sin_iva_default' => '6.50',
            'comision_iva_incluido_default' => '16.00',
            'precio_promocion_multiplicador_default' => '1.09',
            'isr_default' => '0',
            'cancelacion_hipoteca_default' => '20000',
            'monto_credito_default' => '800000',
        ];

        foreach ($configs as $key => $value) {
            ConfigurationCalculator::updateOrCreate(
                ['key' => $key],
                [
                    'name' => ucfirst(str_replace('_', ' ', $key)),
                    'value' => $value,
                    'type' => 'decimal',
                    'group' => 'calculator',
                ]
            );
        }
    }

    #[Test]
    public function it_renders_quote_calculator_page()
    {
        $response = $this->get('/admin/quote-calculator');

        $response->assertStatus(200);
        $response->assertSee('Calculadora de Cotizaciones');
        $response->assertSee('SELECCIÓN DE CLIENTE');
        $response->assertSee('VALOR PRINCIPAL DEL CONVENIO');
    }

    #[Test]
    public function it_shows_calculator_in_quick_mode_by_default()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->assertSee('⚡ Modo Rápido')
            ->assertSee('Calculadora independiente para cálculos rápidos')
            ->assertSet('selectedClientId', null)
            ->assertSet('selectedClientIdxante', null);
    }

    #[Test]
    public function it_switches_to_linked_mode_when_client_selected()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->assertSet('selectedClientId', $this->client->id)
            ->assertSet('selectedClientIdxante', $this->client->xante_id)
            ->assertSee('🔗 Modo Enlazado')
            ->assertSee('Cliente Seleccionado');
    }

    #[Test]
    public function it_loads_default_configuration_on_mount()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->assertSet('data.porcentaje_comision_sin_iva', 6.50)
            ->assertSet('data.iva_percentage', 16.00)
            ->assertSet('data.precio_promocion_multiplicador', 1.09)
            ->assertSet('data.isr', 0)
            ->assertSet('data.cancelacion_hipoteca', 20000)
            ->assertSet('data.monto_credito', 800000);
    }

    #[Test]
    public function it_calculates_values_when_valor_convenio_is_entered()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 1000000)
            ->assertSet('showResults', true)
            ->assertSet('data.precio_promocion', '1,090,000')
            ->assertSet('data.valor_compraventa', '1,000,000.00')
            ->assertSet('data.monto_comision_sin_iva', '65,000.00')
            ->assertSet('data.comision_total_pagar', '75,400.00')
            ->assertSet('data.ganancia_final', '104,600.00');
    }

    #[Test]
    public function it_clears_calculated_fields_when_valor_convenio_is_zero()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 1000000) // Primero calcular
            ->set('data.valor_convenio', 0) // Luego limpiar
            ->assertSet('showResults', false)
            ->assertSet('data.precio_promocion', '')
            ->assertSet('data.valor_compraventa', '')
            ->assertSet('data.ganancia_final', '');
    }

    #[Test]
    public function it_recalculates_when_parameters_change()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 1000000)
            ->set('data.monto_credito', 900000) // Cambiar monto de crédito
            ->assertSet('data.ganancia_final', '4,600.00'); // Nueva ganancia: 1,000,000 - 75,400 - 20,000 - 900,000
    }

    #[Test]
    public function it_can_link_proposal_to_client()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->set('data.estado_propiedad', 'CDMX')
            ->set('data.valor_convenio', 500000)
            ->call('linkProposal');
        // ->assertNotified('✅ Propuesta Enlazada');

        // Verificar que se creó la propuesta en la base de datos
        $this->assertDatabaseHas('proposals', [
            'idxante' => $this->client->xante_id,
            'client_id' => $this->client->id,
            'linked' => true,
            'created_by' => $this->user->id,
        ]);

        $proposal = Proposal::where('idxante', $this->client->xante_id)->first();
        $this->assertEquals(500000, $proposal->data['valor_convenio']);
    }

    #[Test]
    public function it_cannot_link_proposal_without_client()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 500000)
            ->call('linkProposal');
        // ->assertNotified('Debe seleccionar un cliente para enlazar la propuesta.');

        $this->assertDatabaseCount('proposals', 0);
    }

    #[Test]
    public function it_cannot_link_proposal_without_valor_convenio()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->call('linkProposal');
        // ->assertNotified('Debe ingresar un valor de convenio válido antes de enlazar.');

        $this->assertDatabaseCount('proposals', 0);
    }

    #[Test]
    public function it_can_perform_quick_calculate()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 750000)
            ->call('quickCalculate');
        // ->assertNotified('🧮 Cálculo Realizado');

        // Verificar que NO se guardó en la base de datos
        $this->assertDatabaseCount('proposals', 0);
    }

    #[Test]
    public function it_loads_existing_proposal_when_client_selected()
    {
        // Crear una propuesta existente
        $existingData = [
            'valor_convenio' => 600000,
            'isr' => 5000,
            'cancelacion_hipoteca' => 15000,
        ];

        Proposal::create([
            'idxante' => $this->client->xante_id,
            'client_id' => $this->client->id,
            'data' => $existingData,
            'linked' => true,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->assertSet('data.valor_convenio', 600000)
            ->assertSet('data.isr', 5000)
            ->assertSet('data.cancelacion_hipoteca', 15000)
            ->assertSet('data.cancelacion_hipoteca', 15000);
        // ->assertNotified('Propuesta precargada');
    }

    #[Test]
    public function it_can_reset_form()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->set('data.valor_convenio', 500000)
            ->call('resetForm')
            ->assertSet('selectedClientId', null)
            ->assertSet('selectedClientIdxante', null)
            ->assertSet('showResults', false)
            ->assertSet('data.valor_convenio', null)
            ->assertSet('data.valor_convenio', null);
        // ->assertNotified('Formulario reiniciado');
    }

    #[Test]
    public function it_shows_correct_header_actions_for_linked_mode()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->assertSee('Enlazar Valor Propuesta')
            ->assertDontSee('Calcular (Rápido)');
    }

    #[Test]
    public function it_shows_correct_header_actions_for_quick_mode()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->assertSee('Calcular (Rápido)')
            ->assertDontSee('Enlazar Valor Propuesta');
    }

    #[Test]
    public function it_displays_financial_summary_when_calculations_exist()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 1000000)
            ->assertSee('Resumen Financiero')
            ->assertSee('$1,000,000.00') // Valor del convenio
            ->assertSee('$75,400.00') // Comisión total
            ->assertSee('$104,600.00') // Ganancia final
            ->assertSee('✅ Propuesta Rentable');
    }

    #[Test]
    public function it_shows_warning_for_non_profitable_proposals()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 100000) // Valor bajo que resultará en pérdida
            ->set('data.monto_credito', 200000) // Crédito alto
            ->assertSee('⚠️ Revisar Parámetros');
    }

    #[Test]
    public function it_validates_numeric_inputs()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->set('data.estado_propiedad', 'CDMX')
            ->set('data.valor_convenio', 'invalid')
            ->call('linkProposal')
            ->assertHasErrors(['data.valor_convenio']);
    }

    #[Test]
    public function it_updates_calculations_when_operational_costs_change()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 1000000)
            ->set('data.isr', 50000) // Aumentar ISR
            ->set('data.cancelacion_hipoteca', 30000) // Aumentar cancelación
            ->assertSet('data.total_gastos_fi_venta', '80,000.00') // 50,000 + 30,000
            ->assertSet('data.ganancia_final', '44,600.00'); // Ganancia reducida
    }

    #[Test]
    public function it_handles_client_deselection()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.client_id', $this->client->id)
            ->set('data.client_id', null) // Deseleccionar cliente
            ->assertSet('selectedClientId', null)
            ->assertSet('selectedClientIdxante', null)
            ->assertSee('⚡ Modo Rápido');
    }

    #[Test]
    public function it_preserves_calculations_when_switching_modes()
    {
        Livewire::test(QuoteCalculatorPage::class)
            ->set('data.valor_convenio', 500000)
            ->set('data.client_id', $this->client->id) // Cambiar a modo enlazado
            ->assertSet('data.valor_convenio', 500000) // Valor preservado
            ->assertSet('showResults', true); // Cálculos preservados
    }
}
