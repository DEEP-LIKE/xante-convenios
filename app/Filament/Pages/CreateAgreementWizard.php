<?php


namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\Agreement;
use App\Models\Client;
use App\Models\ConfigurationCalculator;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Checkbox;
use App\Jobs\GenerateAgreementDocumentsJob;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use App\Services\PdfGenerationService;
use App\Services\AgreementCalculatorService;
use Illuminate\Support\Carbon;

use BackedEnum;


class CreateAgreementWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $title = 'Nuevo Convenio';
    protected static ?string $navigationLabel = 'Crear Convenio';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'convenios/crear';

    public string $view = 'filament.pages.create-agreement-wizard';

    public ?array $data = [];
    public ?int $agreementId = null;
    public int $currentStep = 1;
    public int $totalSteps = 5;

    protected AgreementCalculatorService $calculatorService;

    protected $listeners = [
        'stepChanged' => 'handleStepChange',
    ];

    public function boot(AgreementCalculatorService $calculatorService): void
    {
        $this->calculatorService = $calculatorService;
    }

    public function mount(?int $agreement = null): void
    {
        // Prioridad 1: Recuperar desde la sesión para persistencia en recargas.
        $agreementId = session('wizard_agreement_id');

        // Prioridad 2: Recuperar desde el parámetro de la ruta o query string.
        if (!$agreementId) {
            $agreementId = $agreement ?? request()->get('agreement');
        }

        $clientId = request()->get('client_id');

        if ($agreementId) {
            $this->agreementId = $agreementId;
            // Guardar en sesión para persistencia
            session(['wizard_agreement_id' => $agreementId]);

            $agreementModel = Agreement::find($agreementId);

            if ($agreementModel) {
                $this->data = $agreementModel->wizard_data ?? [];
                $this->loadCalculatorDefaults();
                $this->currentStep = $agreementModel->current_step ?? 1;

                if ($this->currentStep >= 4) {
                    $this->preloadProposalDataIfExists();
                }

                $this->form->fill($this->data);
            } else {
                // Si el ID no es válido, limpiar la sesión y empezar de nuevo.
                session()->forget('wizard_agreement_id');
                $this->agreementId = null;
                $this->data = [];
                $this->currentStep = 1;
                $this->loadCalculatorDefaults();
                $this->form->fill();
            }
        } else {
            // Flujo para un nuevo convenio sin ID existente.
            $this->agreementId = null;
            $this->data = [];
            $this->currentStep = 1;
            $this->loadCalculatorDefaults();
            $this->form->fill();

            if ($clientId) {
                 Notification::make()
                    ->title('Funcionalidad en desarrollo')
                    ->body("La preselección de clientes desde la URL se activará después de guardar el primer paso.")
                    ->warning()
                    ->send();
            }
        }
    }

    protected function getFormSchema(): array
    {
        return [
                Wizard::make([
                    Step::make('Identificación')
                        ->description('Búsqueda y selección del cliente')
                        ->icon('heroicon-o-magnifying-glass')
                        ->afterValidation(function ($state) {
                            // Si no tenemos un ID de convenio, es la primera vez que pasamos este paso.
                            if (!$this->agreementId) {
                                $client = Client::find($state['client_id']);

                                // Crear el convenio por primera vez
                                $agreement = Agreement::create([
                                    'status' => 'expediente_incompleto',
                                    'current_step' => 1,
                                    'created_by' => Auth::id(),
                                    'client_id' => $state['client_id'],
                                    'client_xante_id' => $client ? $client->xante_id : null,
                                ]);

                                $this->agreementId = $agreement->id;

                                // Guardar en sesión para persistencia en recargas
                                session(['wizard_agreement_id' => $this->agreementId]);
                            }

                            // Guardar los datos del paso actual
                            $this->saveStepData(1);
                        })
                        ->schema([
                            Select::make('client_id')
                                ->label('Cliente Seleccionado')
                                ->placeholder('Busque por nombre o ID Xante...')
                                ->options(function () {
                                    return Client::query()
                                        ->selectRaw("id, CONCAT(name, ' — ', xante_id) as display_name")
                                        ->pluck('display_name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $this->preloadClientData($state, $set);
                                    }
                                })
                                ->suffixAction(
                                    Action::make('sync_search')
                                        ->label('Sincronizar') // Etiqueta más corta
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('success') // Verde Lima Xante
                                        ->action(function () {
                                            Notification::make()
                                                ->title('Sincronización Iniciada')
                                                ->body('La sincronización con Hubspot de la fuente de datos externa ha comenzado.')
                                                ->warning()
                                                ->icon('heroicon-o-arrow-path')
                                                ->send();
                                        })
                                ),
                        ]),

                    Step::make('Cliente')
                        ->description('Información personal del cliente')
                        ->icon('heroicon-o-user')
                        ->afterValidation(function () {
                            $this->saveStepData(2);
                        })
                        ->schema([
                            // DATOS GENERALES - FASE I
                            Section::make('DATOS GENERALES "FASE I"')
                                ->description('Información básica del convenio')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('xante_id')
                                                ->label('ID Xante')
                                                ->disabled()
                                                ->dehydrated(false),
                                            DatePicker::make('fecha_registro')
                                                ->label('Fecha')
                                                ->displayFormat('d/m/Y')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->suffixIcon(Heroicon::Calendar),
                                        ]),
                                ])
                                ->collapsible(),

                            // DATOS PERSONALES TITULAR
                            Section::make('DATOS PERSONALES TITULAR:')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('holder_name')
                                                ->label('Nombre Cliente')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('holder_delivery_file')
                                                ->label('Entrega expediente')
                                                ->maxLength(100),
                                            DatePicker::make('spouse_birthdate')
                                                ->native(false)
                                                ->label('Fecha de Nacimiento (min 18 años)')
                                                ->displayFormat('d/m/Y')
                                                ->maxDate(Carbon::today()->subYears(18))
                                                ->validationMessages([
                                                    'max' => 'El titular debe ser mayor de 18 años.',
                                                ])
                                                ->suffixIcon(Heroicon::Calendar),
                                            Select::make('holder_civil_status')
                                                ->label('Estado civil')
                                                ->options([
                                                    'soltero' => 'Soltero(a)',
                                                    'casado' => 'Casado(a)',
                                                    'divorciado' => 'Divorciado(a)',
                                                    'viudo' => 'Viudo(a)',
                                                    'union_libre' => 'Unión Libre',
                                                ]),
                                            TextInput::make('holder_curp')
                                                ->label('CURP')
                                                ->maxLength(18)
                                                ->minLength(18),
                                            TextInput::make('holder_regime_type')
                                                ->label('Régimen Fiscal')
                                                ->maxLength(100),
                                            TextInput::make('holder_rfc')
                                                ->label('RFC')
                                                ->maxLength(13),
                                            TextInput::make('holder_occupation')
                                                ->label('Ocupación')
                                                ->maxLength(100),
                                            TextInput::make('holder_email')
                                                ->label('Correo electrónico')
                                                ->email()
                                                ->required(),
                                            TextInput::make('holder_office_phone')
                                                ->label('Tel. oficina')
                                                ->tel()
                                                ->maxLength(20),
                                            TextInput::make('holder_phone')
                                                ->label('Núm. Celular')
                                                ->tel()
                                                ->required()
                                                ->maxLength(20),
                                            TextInput::make('holder_additional_contact_phone')
                                                ->label('Tel. Contacto Adic.')
                                                ->tel()
                                                ->maxLength(20),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('current_address')
                                                ->label('Calle y Domicilio')
                                                ->maxLength(400)
                                                ->columnSpan(1),
                                            TextInput::make('holder_house_number')
                                                ->label('Número')
                                                ->maxLength(20)
                                                ->columnSpan(1),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('neighborhood')
                                                ->label('Colonia')
                                                ->maxLength(100),
                                            TextInput::make('postal_code')
                                                ->label('C.P.')
                                                ->maxLength(10),
                                            TextInput::make('municipality')
                                                ->label('Municipio - Alcaldía')
                                                ->maxLength(100),
                                            TextInput::make('state')
                                                ->label('Estado')
                                                ->maxLength(100),
                                        ]),
                                ])
                                ->collapsible(),

                            // DATOS PERSONALES COACREDITADO / CÓNYUGE
                            Section::make('DATOS PERSONALES COACREDITADO / CÓNYUGE:')
                                ->description('Información del cónyuge o coacreditado')
                                ->headerActions([
                                    Action::make('copy_from_holder')
                                        ->label('Copiar datos del titular')
                                        ->icon('heroicon-o-document-duplicate')
                                        ->color('gray')
                                        ->size('sm')
                                        ->action(function (callable $set, callable $get) {
                                            // Copiar datos de domicilio
                                            $set('spouse_current_address', $get('current_address'));
                                            $set('spouse_house_number', $get('holder_house_number'));
                                            $set('spouse_neighborhood', $get('neighborhood'));
                                            $set('spouse_postal_code', $get('postal_code'));
                                            $set('spouse_municipality', $get('municipality'));
                                            $set('spouse_state', $get('state'));

                                            // Copiar datos de teléfono
                                            $set('spouse_phone', $get('holder_phone'));
                                            $set('spouse_office_phone', $get('holder_office_phone'));
                                            $set('spouse_additional_contact_phone', $get('holder_additional_contact_phone'));

                                            Notification::make()
                                                ->title('Datos copiados exitosamente')
                                                ->body('Los datos de domicilio y teléfono del titular han sido copiados al cónyuge.')
                                                ->success()
                                                ->duration(5000)
                                                ->send();
                                        })
                                        ->tooltip('Copiar domicilios y teléfonos del titular'),
                                ])
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('spouse_name')
                                                ->label('Nombre Cliente')
                                                ->maxLength(255),
                                            TextInput::make('spouse_delivery_file')
                                                ->label('Entrega expediente')
                                                ->maxLength(100),
                                            DatePicker::make('spouse_birthdate')
                                                ->native(false)
                                                ->label('Fecha de Nacimiento (min 18 años)')
                                                ->displayFormat('d/m/Y')
                                                ->maxDate(Carbon::today()->subYears(18))
                                                ->validationMessages([
                                                    'max' => 'El titular debe ser mayor de 18 años.',
                                                ])
                                                ->suffixIcon(Heroicon::Calendar),
                                            Select::make('spouse_civil_status')
                                                ->label('Estado civil')
                                                ->options([
                                                    'soltero' => 'Soltero(a)',
                                                    'casado' => 'Casado(a)',
                                                    'divorciado' => 'Divorciado(a)',
                                                    'viudo' => 'Viudo(a)',
                                                    'union_libre' => 'Unión Libre',
                                                ]),
                                            TextInput::make('spouse_curp')
                                                ->label('CURP')
                                                ->maxLength(18)
                                                ->minLength(18),
                                            TextInput::make('spouse_regime_type')
                                                ->label('Régimen Fiscal')
                                                ->maxLength(100),
                                            TextInput::make('spouse_rfc')
                                                ->label('RFC')
                                                ->maxLength(13),
                                            TextInput::make('spouse_occupation')
                                                ->label('Ocupación')
                                                ->maxLength(100),
                                            TextInput::make('spouse_email')
                                                ->label('Correo electrónico')
                                                ->email(),
                                            TextInput::make('spouse_office_phone')
                                                ->label('Tel. oficina')
                                                ->tel()
                                                ->maxLength(20),
                                            TextInput::make('spouse_phone')
                                                ->label('Núm. Celular')
                                                ->tel()
                                                ->maxLength(20),
                                            TextInput::make('spouse_additional_contact_phone')
                                                ->label('Tel. Contacto Adic.')
                                                ->tel()
                                                ->maxLength(20),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('spouse_current_address')
                                                ->label('Calle y Domicilio')
                                                ->maxLength(400)
                                                ->columnSpan(1),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('spouse_neighborhood')
                                                ->label('Colonia')
                                                ->maxLength(100),
                                            TextInput::make('spouse_postal_code')
                                                ->label('C.P.')
                                                ->maxLength(10),
                                            TextInput::make('spouse_municipality')
                                                ->label('Municipio - Alcaldía')
                                                ->maxLength(100),
                                            TextInput::make('spouse_state')
                                                ->label('Estado')
                                                ->maxLength(100),
                                        ]),
                                ])
                                ->collapsible(),

                            // CONTACTO AC Y/O PRESIDENTE DE PRIVADA
                            Section::make('CONTACTO AC Y/O PRESIDENTE DE PRIVADA')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('ac_name')
                                                ->label('NOMBRE AC')
                                                ->maxLength(255),
                                            TextInput::make('private_president_name')
                                                ->label('PRESIDENTE PRIVADA')
                                                ->maxLength(255),
                                            TextInput::make('ac_phone')
                                                ->label('Núm. Celular')
                                                ->tel()
                                                ->maxLength(20),
                                            TextInput::make('private_president_phone')
                                                ->label('Núm. Celular')
                                                ->tel()
                                                ->maxLength(20),
                                            TextInput::make('ac_quota')
                                                ->label('CUOTA')
                                                ->numeric()
                                                ->prefix('$'),
                                            TextInput::make('private_president_quota')
                                                ->label('CUOTA')
                                                ->numeric()
                                                ->prefix('$'),
                                        ]),
                                ])
                                ->collapsible(),
                        ]),
                    Step::make('Propiedad')
                        ->description('Datos de la vivienda y ubicación')
                        ->icon('heroicon-o-home-modern')
                        ->afterValidation(function () {
                            $this->saveStepData(3);
                        })
                        ->schema([
                            Section::make('INFORMACIÓN DE LA PROPIEDAD')
                                ->description('Datos de ubicación y características de la vivienda')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('domicilio_convenio')
                                                ->label('Domicilio Viv. Convenio')
                                                ->maxLength(255)
                                                ->required(),
                                            TextInput::make('comunidad')
                                                ->label('Comunidad')
                                                ->maxLength(255)
                                                ->required(),
                                            TextInput::make('tipo_vivienda')
                                                ->label('Tipo de Vivienda')
                                                ->maxLength(100)
                                                ->required(),
                                            TextInput::make('prototipo')
                                                ->label('Prototipo')
                                                ->maxLength(100)
                                                ->required(),
                                        ]),
                                ])
                                ->collapsible(),

                            Section::make('DATOS ADICIONALES')
                                ->description('Información complementaria de la propiedad')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('lote')
                                                ->label('Lote')
                                                ->maxLength(50)
                                                ->required(),
                                            TextInput::make('manzana')
                                                ->label('Manzana')
                                                ->maxLength(50)
                                                ->required(),
                                            TextInput::make('etapa')
                                                ->label('Etapa')
                                                ->maxLength(50)
                                                ->required(),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('municipio_propiedad')
                                                ->label('Municipio')
                                                ->maxLength(100)
                                                ->required(),
                                            TextInput::make('estado_propiedad')
                                                ->label('Estado')
                                                ->maxLength(100)
                                                ->required(),
                                        ]),
                                    Grid::make(1)
                                        ->schema([
                                            DatePicker::make('fecha_propiedad')
                                                ->label('Fecha escrituración')
                                                ->native(false)
                                                ->displayFormat('d/m/Y')
                                                ->suffixIcon(Heroicon::Calendar)
                                                ->required()
                                                ->maxDate(Carbon::today()->subYears(3)) // ✅ Esta línea hace la validación
                                                ->validationMessages([
                                                    'max' => 'La propiedad debe tener una antigüedad mínima de 3 años.' // ✅ Y este es el mensaje
                                                ]),
                                        ]),
                                ])
                                ->collapsible(),
                        ]),
                    Step::make('Calculadora')
                        ->description('Cálculos financieros del convenio')
                        ->icon('heroicon-o-calculator')
                        ->afterValidation(function () {
                            $this->saveStepData(4);
                        })
                        ->schema([
                            // ⭐ NUEVO: Indicador de Pre-cálculo Previo
                            Section::make('💡 PRE-CÁLCULO DETECTADO')
                                ->description('Este cliente ya tiene una cotización previa registrada')
                                ->schema([
                                    Placeholder::make('existing_proposal_alert')
                                        ->label('')
                                        ->content(function () {
                                            $proposalInfo = $this->hasExistingProposal();
                                            
                                            if (!$proposalInfo) {
                                                return ''; // No mostrar nada si no existe
                                            }
                                            
                                            $valorConvenio = $proposalInfo['valor_convenio'] ?? 0;
                                            $gananciaFinal = $proposalInfo['ganancia_final'] ?? 0;
                                            $fechaCalculo = $proposalInfo['created_at']->format('d/m/Y H:i');
                                            
                                            return new HtmlString('
                                                <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); 
                                                            border-left: 4px solid #F59E0B; 
                                                            padding: 20px; 
                                                            border-radius: 12px;
                                                            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);">
                                                    <div style="display: flex; align-items: start; gap: 16px;">
                                                        <!-- Icono -->
                                                        <div style="flex-shrink: 0;">
                                                            <svg style="width: 32px; height: 32px; color: #D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                        </div>
                                                        
                                                        <!-- Contenido -->
                                                        <div style="flex: 1;">
                                                            <h3 style="font-size: 18px; font-weight: 700; color: #92400E; margin: 0 0 12px 0;">
                                                                ✅ Pre-cálculo Previo Detectado
                                                            </h3>
                                                            
                                                            <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                                                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 14px;">
                                                                    <div>
                                                                        <strong style="color: #78350F;">📅 Fecha del cálculo:</strong><br>
                                                                        <span style="color: #92400E;">' . $fechaCalculo . '</span>
                                                                    </div>
                                                                    <div>
                                                                        <strong style="color: #78350F;">💰 Valor Convenio:</strong><br>
                                                                        <span style="color: #92400E; font-weight: 600;">$' . number_format($valorConvenio, 2) . '</span>
                                                                    </div>
                                                                    <div>
                                                                        <strong style="color: #78350F;">💵 Ganancia Estimada:</strong><br>
                                                                        <span style="color: #059669; font-weight: 700; font-size: 16px;">$' . number_format($gananciaFinal, 2) . '</span>
                                                                    </div>
                                                                    <div>
                                                                        <strong style="color: #78350F;">📊 Estado:</strong><br>
                                                                        <span style="background: #D97706; color: white; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600;">ENLAZADO</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <p style="font-size: 13px; color: #B45309; margin: 0; font-style: italic;">
                                                                ℹ️ Los valores han sido precargados automáticamente desde la calculadora previa. Puede modificarlos si es necesario.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ');
                                        })
                                        ->html(),
                                ])
                                ->visible(fn () => $this->hasExistingProposal() !== null)
                                ->collapsible()
                                ->collapsed(false),

                            // Información de la Propiedad (Precargada)
                            Section::make('INFORMACIÓN DE LA PROPIEDAD')
                                ->description('Datos precargados desde pasos anteriores')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('domicilio_convenio')
                                                ->label('Domicilio Viv. Convenio')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                            TextInput::make('comunidad')
                                                ->label('Comunidad')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                            TextInput::make('tipo_vivienda')
                                                ->label('Tipo de Vivienda')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                            TextInput::make('prototipo')
                                                ->label('Prototipo')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('lote')
                                                ->label('Lote')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                            TextInput::make('manzana')
                                                ->label('Manzana')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                            TextInput::make('etapa')
                                                ->label('Etapa')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('municipio_propiedad')
                                                ->label('Municipio')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                            TextInput::make('estado_propiedad')
                                                ->label('Estado')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50']),
                                        ]),
                                ])
                                ->collapsible(),

                            // Campo Principal: Valor Convenio
                            Section::make('VALOR PRINCIPAL DEL CONVENIO')
                                ->description('Campo principal que rige todos los cálculos financieros')
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                            TextInput::make('valor_convenio')
                                                ->label('Valor Convenio')
                                                ->numeric()
                                                ->prefix('$')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    if ($state && $state > 0) {
                                                        $this->recalculateAllFinancials($set, $get);
                                                    } else {
                                                        // Limpiar todos los campos calculados si no hay valor
                                                        $this->clearCalculatedFields($set);
                                                    }
                                                })
                                                ->helperText('Ingrese el valor del convenio para activar todos los cálculos automáticos')
                                                ->extraAttributes(['class' => 'text-lg font-semibold']),
                                        ]),
                                ])
                                ->collapsible(),



                            // Configuración de Parámetros
                            Section::make('PARÁMETROS DE CÁLCULO')
                                ->description('Configuración de porcentajes y valores base')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('porcentaje_comision_sin_iva')
                                                ->label('% Comisión (Sin IVA)')
                                                ->numeric()
                                                ->suffix('%')
                                                ->step(0.01)
                                                ->default(function () {
                                                    $config = ConfigurationCalculator::where('key', 'comision_sin_iva_default')->first();
                                                    return $config ? $config->value : 6.50;
                                                })
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50'])
                                                ->helperText('Valor fijo desde configuración'),
                                            TextInput::make('porcentaje_comision_iva_incluido')
                                                ->label('% Comisión IVA Incluido')
                                                ->numeric()
                                                ->suffix('%')
                                                ->step(0.01)
                                                ->default(function () {
                                                    $config = ConfigurationCalculator::where('key', 'comision_iva_incluido_default')->first();
                                                    return $config ? $config->value : 7.54;
                                                })
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50'])
                                                ->helperText('Valor fijo desde configuración'),
                                            TextInput::make('precio_promocion_multiplicador')
                                                ->label('Multiplicador Precio Promoción')
                                                ->numeric()
                                                ->step(0.01)
                                                ->default(function () {
                                                    $config = ConfigurationCalculator::where('key', 'precio_promocion_multiplicador_default')->first();
                                                    return $config ? $config->value : 1.09;
                                                })
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-gray-50'])
                                                ->helperText('Valor fijo desde configuración'),
                                            TextInput::make('monto_credito')
                                                ->label('Monto de Crédito')
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(function () {
                                                    $config = ConfigurationCalculator::where('key', 'monto_credito_default')->first();
                                                    return $config ? $config->value : 800000;
                                                })
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    if ($get('valor_convenio')) {
                                                        $this->recalculateAllFinancials($set, $get);
                                                    }
                                                })
                                                ->helperText('Valor editable - precargado desde configuración'),
                                            Select::make('tipo_credito')
                                                ->label('Tipo de Crédito')
                                                ->options([
                                                    'bancario' => 'Bancario',
                                                    'infonavit' => 'Infonavit',
                                                    'fovissste' => 'Fovissste',
                                                    'otro' => 'Otro',
                                                ]),
                                        ]),
                                ])
                                ->collapsible(),

                            // Valores Calculados Automáticamente
                            Section::make('VALORES CALCULADOS')
                                ->description('Estos valores se calculan automáticamente al ingresar el Valor Convenio')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('precio_promocion')
                                                ->label('Precio Promoción')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-blue-50 text-blue-800 font-semibold'])
                                                ->helperText('Valor Convenio × Multiplicador Precio Promoción'),
                                            TextInput::make('valor_compraventa')
                                                ->label('Valor CompraVenta')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-blue-50 text-blue-800 font-semibold'])
                                                ->helperText('Espejo del Valor Convenio'),
                                            TextInput::make('monto_comision_sin_iva')
                                                ->label('Monto Comisión (Sin IVA)')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                                ->helperText('Valor Convenio × % Comisión'),
                                            TextInput::make('comision_total_pagar')
                                                ->label('Comisión Total a Pagar')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                                ->helperText('Valor Convenio × % Comisión IVA Incluido'),
                                        ]),
                                ])
                                ->collapsible(),

                            // Costos de Operación
                            Section::make('COSTOS DE OPERACIÓN')
                                ->description('Campos editables para gastos adicionales')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('isr')
                                                ->label('ISR')
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    if ($get('valor_convenio')) {
                                                        $this->recalculateAllFinancials($set, $get);
                                                    }
                                                }),
                                            TextInput::make('cancelacion_hipoteca')
                                                ->label('Cancelación de Hipoteca')
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(20000)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    if ($get('valor_convenio')) {
                                                        $this->recalculateAllFinancials($set, $get);
                                                    }
                                                }),
                                            TextInput::make('total_gastos_fi_venta')
                                                ->label('Total Gastos FI (Venta)')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                                ->helperText('ISR + Cancelación de Hipoteca'),
                                            TextInput::make('ganancia_final')
                                                ->label('Ganancia Final (Est.)')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'bg-green-50 text-green-800 font-bold text-lg'])
                                                ->helperText('Valor CompraVenta - ISR - Cancelación - Comisión Total - Monto Crédito'),
                                        ]),
                                ])
                                ->collapsible(),
                        ]),

                    Step::make('Validación')
                        ->description('Resumen y confirmación de datos')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->afterValidation(function () {
                            $this->saveStepData(5);
                        })
                        ->schema([
                            // Grid de 3 columnas para las secciones principales
                            Grid::make(3)
                                ->schema([
                                    // SECCIÓN 1: DATOS DEL TITULAR
                                    Section::make('👤 DATOS DEL TITULAR')
                                        ->description('Información del titular capturada en Paso 2')
                                        ->schema([
                                            Placeholder::make('holder_summary')
                                                ->content(function () {
                                                    return $this->renderHolderSummary($this->data);
                                                })
                                                ->html(),
                                        ])
                                        ->collapsible()
                                        ->collapsed(false),

                                    // SECCIÓN 2: DATOS DEL CÓNYUGE/COACREDITADO
                                    Section::make('💑 DATOS DEL CÓNYUGE')
                                        ->description('Información del cónyuge/coacreditado capturada en Paso 2')
                                        ->schema([
                                            Placeholder::make('spouse_summary')
                                                ->content(function () {
                                                    return $this->renderSpouseSummary($this->data);
                                                })
                                                ->html(),
                                        ])
                                        ->collapsible()
                                        ->collapsed(false),

                                    // SECCIÓN 3: DATOS DE LA PROPIEDAD
                                    Section::make('🏠 DATOS DE LA PROPIEDAD')
                                        ->description('Información capturada en Paso 3')
                                        ->schema([
                                            Placeholder::make('property_summary')
                                                ->content(function () {
                                                    return $this->renderPropertySummary($this->data);
                                                })
                                                ->html(),
                                        ])
                                        ->collapsible()
                                        ->collapsed(false),
                                ]),

                            // SECCIÓN 4: CALCULADORA FINANCIERA (ancho completo)
                            Section::make('💰 RESUMEN FINANCIERO')
                                ->description('Cálculos realizados en Paso 4')
                                ->schema([
                                    Placeholder::make('financial_summary')
                                        ->content(function () {
                                            return $this->renderFinancialSummary($this->data);
                                        })
                                        ->html(),
                                ])
                                ->collapsible()
                                ->collapsed(false)
                                ->columnSpanFull(),

                            // SECCIÓN DE CONFIRMACIÓN
                            Section::make('CONFIRMACIÓN FINAL')
                                ->schema([
                                    Placeholder::make('warning_message')
                                        ->content(new HtmlString('
                                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px;">
                                                <div style="display: flex;">
                                                    <div style="flex-shrink: 0; margin-right: 12px;">
                                                        <svg style="height: 20px; width: 20px; color: #f59e0b;" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h3 style="font-size: 14px; font-weight: 500; color: #92400e; margin: 0;">
                                                            Una vez que genere los documentos, NO PODRÁ modificar esta información
                                                        </h3>
                                                        <div style="margin-top: 8px; font-size: 14px; color: #b45309;">
                                                            <p style="margin: 0;">Los documentos PDF se generarán con la información que aparece arriba. Revise cuidadosamente antes de continuar.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        '))
                                        ->hiddenLabel(),

                                    Checkbox::make('confirm_data_correct')
                                        ->label('✓ Confirmo que he revisado toda la información y es correcta')
                                        ->required()
                                        ->accepted()
                                        ->validationMessages([
                                            'accepted' => 'Debe confirmar que la información es correcta para continuar.',
                                        ])
                                        ->helperText('Esta confirmación es obligatoria para poder generar los documentos')
                                        ->inline(false)
                                        ->dehydrated(),
                                ])
                                ->columnSpanFull(),
                        ]),
                ])
                ->submitAction(
                    // 💡 CORRECCIÓN: Definir el Action aquí y aplicar requiereConfirmation()
                    Action::make('submit')
                        // 1. Asignar la acción real del componente Livewire
                        ->action('generateDocumentsAndProceed') 
                        // 2. Personalizar el botón de envío
                        ->label('Validar y Generar Documentos')
                        ->icon('heroicon-o-document-plus')
                        ->color('danger')
                        // 3. AGREGAR LA CONFIRMACIÓN AQUÍ
                        ->requiresConfirmation()
                        // Opcional: Personalizar el contenido del modal
                        ->modalHeading('Confirmar Envío')
                        ->modalDescription('¿Estás seguro de que desea finalizar y generar los documentos?')
                        ->modalSubmitActionLabel('Sí, Confirmar')
                )

                ->nextAction(fn (Action $action) => $action->label('Siguiente'))
                ->previousAction(fn (Action $action) => $action->label('Anterior'))
                // ->cancelAction('Cancelar')
                ->persistStepInQueryString()
                ->startOnStep($this->currentStep)
                ->skippable(false)
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function loadCalculatorDefaults(): void
    {
        // Usar el servicio para obtener la configuración por defecto
        $defaults = $this->calculatorService->getDefaultConfiguration();

        // Solo aplicar defaults si no hay datos existentes (para no sobrescribir datos cargados)
        $this->data = array_merge($defaults, $this->data);
    }

    /**
     * Recalcula TODOS los valores financieros cuando se ingresa o modifica el Valor Convenio
     */
    protected function recalculateAllFinancials(callable $set, callable $get): void
    {
        $valorConvenio = (float) ($get('valor_convenio') ?? 0);
        
        if ($valorConvenio <= 0) {
            $this->clearCalculatedFields($set);
            return;
        }

        // Obtener parámetros actuales del formulario
        $parameters = [
            'porcentaje_comision_sin_iva' => (float) ($get('porcentaje_comision_sin_iva') ?? 6.50),
            'porcentaje_comision_iva_incluido' => (float) ($get('porcentaje_comision_iva_incluido') ?? 7.54),
            'precio_promocion_multiplicador' => (float) ($get('precio_promocion_multiplicador') ?? 1.09),
            'isr' => (float) ($get('isr') ?? 0),
            'cancelacion_hipoteca' => (float) ($get('cancelacion_hipoteca') ?? 20000),
            'monto_credito' => (float) ($get('monto_credito') ?? 800000),
        ];

        // Usar el servicio para calcular
        $calculations = $this->calculatorService->calculateAllFinancials($valorConvenio, $parameters);
        $formattedValues = $this->calculatorService->formatCalculationsForUI($calculations);

        // Aplicar valores calculados al formulario
        foreach ($formattedValues as $field => $value) {
            $set($field, $value);
        }
    }

    /**
     * Limpia todos los campos calculados cuando no hay Valor Convenio
     */
    protected function clearCalculatedFields(callable $set): void
    {
        $set('precio_promocion', '');
        $set('valor_compraventa', '');
        $set('monto_comision_sin_iva', '');
        $set('comision_total_pagar', '');
        $set('total_gastos_fi_venta', '');
        $set('ganancia_final', '');
    }

    /**
     * Método legacy mantenido para compatibilidad
     */
    protected function recalculateFinancials(callable $set, callable $get): void
    {
        // Redirigir al nuevo método
        $this->recalculateAllFinancials($set, $get);
    }

    public function saveStepData(int $step): void
    {
        try {

            // CRÍTICO: Obtener datos sin validación para evitar errores de campos obligatorios
            try {
                $formData = $this->form->getRawState();
            } catch (\Exception $e) {
                // Si falla getRawState(), usar los datos actuales
                $formData = $this->data ?? [];
            }

            // Actualizar $this->data con los datos del formulario
            $this->data = array_merge($this->data ?? [], $formData);


            if (!$this->agreementId) {

                Notification::make()
                    ->title('Error de guardado')
                    ->body('No se pudo identificar el convenio. Por favor, intente nuevamente.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return;
            }

            $agreement = Agreement::find($this->agreementId);

            if (!$agreement) {

                Notification::make()
                    ->title('Error de guardado')
                    ->body('No se encontró el convenio en la base de datos.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return;
            }

            // Actualizar la propiedad currentStep local
            $this->currentStep = $step;

            // Actualizar datos del convenio con manejo de errores
            try {
                $updated = $agreement->update([
                    'current_step' => $step,
                    'wizard_data' => $this->data,
                    'updated_at' => now(),
                ]);


            } catch (\Exception $e) {

                Notification::make()
                    ->title('⚠️ Error de guardado')
                    ->body('Error al guardar en BD: ' . $e->getMessage())
                    ->danger()
                    ->duration(8000)
                    ->send();
                return;
            }

            if (!$updated) {

                Notification::make()
                    ->title('Error de guardado')
                    ->body('No se pudieron guardar los datos. Por favor, intente nuevamente.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return;
            }

            // Calcular porcentaje de completitud usando el método del modelo
            $agreement->calculateCompletionPercentage();

            // Actualizar la URL para incluir el agreement ID (solo si no está presente)
            if (!request()->has('agreement')) {
                $this->dispatch('update-query-string', ['agreement' => $this->agreementId]);
            }

            // Mostrar notificación de guardado automático
            if ($step >= 1) {
                Notification::make()
                    ->title("Guardando")
                    ->body("Se ha guardado el paso #{$step}")
                    ->icon('heroicon-o-server')
                    ->success()
                    ->duration(4000)
                    ->send();

            }

            // Si estamos en el paso 2 y hay un cliente seleccionado, actualizar sus datos
            if ($step === 2 && isset($this->data['client_id']) && $this->data['client_id']) {
                $this->updateClientData($this->data['client_id']);
            }

            // Si estamos llegando al paso 4 (Calculadora), precargar datos de propiedad
            // if ($step === 3) {
            //     $this->preloadPropertyDataForCalculator();
            // }

        } catch (\Exception $e) {

            Notification::make()
                ->title('Error inesperado')
                ->body('Ocurrió un error al guardar: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        }
    }

    public function updateClientData(int $clientId): void
    {
        $client = Client::find($clientId);

        if ($client) {
            $updateData = [];

            // Mapear campos del wizard a campos del cliente
            $fieldMapping = [
                // Datos personales titular
                'name' => 'holder_name',
                'birthdate' => 'holder_birthdate',
                'curp' => 'holder_curp',
                'rfc' => 'holder_rfc',
                'email' => 'holder_email',
                'phone' => 'holder_phone',
                'delivery_file' => 'holder_delivery_file',
                'civil_status' => 'holder_civil_status',
                'regime_type' => 'holder_regime_type',
                'occupation' => 'holder_occupation',
                'office_phone' => 'holder_office_phone',
                'additional_contact_phone' => 'holder_additional_contact_phone',
                'current_address' => 'holder_current_address',
                'neighborhood' => 'holder_neighborhood',
                'postal_code' => 'holder_postal_code',
                'municipality' => 'holder_municipality',
                'state' => 'holder_state',

                // Datos cónyuge
                'spouse_name' => 'spouse_name',
                'spouse_birthdate' => 'spouse_birthdate',
                'spouse_curp' => 'spouse_curp',
                'spouse_rfc' => 'spouse_rfc',
                'spouse_email' => 'spouse_email',
                'spouse_phone' => 'spouse_phone',
                'spouse_delivery_file' => 'spouse_delivery_file',
                'spouse_civil_status' => 'spouse_civil_status',
                'spouse_regime_type' => 'spouse_regime_type',
                'spouse_occupation' => 'spouse_occupation',
                'spouse_office_phone' => 'spouse_office_phone',
                'spouse_additional_contact_phone' => 'spouse_additional_contact_phone',
                'spouse_current_address' => 'spouse_current_address',
                'spouse_neighborhood' => 'spouse_neighborhood',
                'spouse_postal_code' => 'spouse_postal_code',
                'spouse_municipality' => 'spouse_municipality',
                'spouse_state' => 'spouse_state',

                // Contactos AC/Presidente
                'ac_name' => 'ac_name',
                'ac_phone' => 'ac_phone',
                'ac_quota' => 'ac_quota',
                'private_president_name' => 'private_president_name',
                'private_president_phone' => 'private_president_phone',
                'private_president_quota' => 'private_president_quota',
            ];

            // Solo actualizar campos que han cambiado
            foreach ($fieldMapping as $clientField => $wizardField) {
                if (isset($this->data[$wizardField]) && $this->data[$wizardField] !== null) {
                    $updateData[$clientField] = $this->data[$wizardField];
                }
            }

            if (!empty($updateData)) {
                $client->update($updateData);

                Notification::make()
                    ->title('Cliente actualizado')
                    ->body('Los datos del cliente han sido actualizados exitosamente.')
                    ->success()
                    ->send();
            }
        }
    }

    /**
     * Precarga los datos de la propiedad desde el paso 3 al llegar al paso 4 (Calculadora)
     */
    // protected function preloadPropertyDataForCalculator(): void
    // {
    //     // Los datos ya están en $this->data, solo necesitamos asegurar que estén disponibles
    //     // para la calculadora. Filament manejará automáticamente la sincronización.

    //     // Notificar al usuario que los datos han sido precargados
    //     if (!empty($this->data['domicilio_convenio']) || !empty($this->data['comunidad'])) {
    //         Notification::make()
    //             ->title('Datos de propiedad precargados')
    //             ->body('Los datos de la propiedad han sido precargados desde el paso anterior.')
    //             ->success()
    //             ->send();
    //     }
    // }

    public function searchClient(): void
    {
        $searchTerm = $this->data['search_term'] ?? '';

        if (!empty($searchTerm)) {
            // Buscar clientes que coincidan
            $clients = Client::where('name', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%")
                ->orWhere('xante_id', 'like', "%{$searchTerm}%")
                ->limit(10)
                ->get();

            if ($clients->count() > 0) {
                // Si encontramos clientes, auto-seleccionar el primero
                $firstClient = $clients->first();
                $this->data['client_id'] = $firstClient->id;

                // Precargar todos los datos del cliente
                $this->preloadClientData($firstClient->id, function($field, $value) {
                    $this->data[$field] = $value;
                });

                Notification::make()
                    ->title('Cliente encontrado y precargado')
                    ->body("Se encontró: {$firstClient->name}. Los datos han sido precargados en el paso 2.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Sin resultados')
                    ->body('No se encontraron clientes. Puede crear uno nuevo en el siguiente paso.')
                    ->warning()
                    ->send();
            }
        }
    }

    public function preloadClientData(int $clientId, callable $set): void
    {
        $client = Client::find($clientId);

        if ($client) {
            // Datos generales
            $set('xante_id', $client->xante_id);

            // Datos personales titular
            $set('holder_name', $client->name);
            $set('holder_birthdate', $client->birthdate);
            $set('holder_curp', $client->curp);
            $set('holder_rfc', $client->rfc);
            $set('holder_email', $client->email);
            $set('holder_phone', $client->phone);
            $set('holder_delivery_file', $client->delivery_file);
            $set('holder_civil_status', $client->civil_status);
            $set('holder_regime_type', $client->regime_type);
            $set('holder_occupation', $client->occupation);
            $set('holder_office_phone', $client->office_phone);
            $set('holder_additional_contact_phone', $client->additional_contact_phone);
            $set('holder_current_address', $client->current_address);
            $set('holder_neighborhood', $client->neighborhood);
            $set('holder_postal_code', $client->postal_code);
            $set('holder_municipality', $client->municipality);
            $set('holder_state', $client->state);

            // Datos cónyuge
            $set('spouse_name', $client->spouse_name);
            $set('spouse_birthdate', $client->spouse_birthdate);
            $set('spouse_curp', $client->spouse_curp);
            $set('spouse_rfc', $client->spouse_rfc);
            $set('spouse_email', $client->spouse_email);
            $set('spouse_phone', $client->spouse_phone);
            $set('spouse_delivery_file', $client->spouse_delivery_file);
            $set('spouse_civil_status', $client->spouse_civil_status);
            $set('spouse_regime_type', $client->spouse_regime_type);
            $set('spouse_occupation', $client->spouse_occupation);
            $set('spouse_office_phone', $client->spouse_office_phone);
            $set('spouse_additional_contact_phone', $client->spouse_additional_contact_phone);
            $set('spouse_current_address', $client->spouse_current_address);
            $set('spouse_neighborhood', $client->spouse_neighborhood);
            $set('spouse_postal_code', $client->spouse_postal_code);
            $set('spouse_municipality', $client->spouse_municipality);
            $set('spouse_state', $client->spouse_state);

            // Contactos AC/Presidente
            $set('ac_name', $client->ac_name);
            $set('ac_phone', $client->ac_phone);
            $set('ac_quota', $client->ac_quota);
            $set('private_president_name', $client->private_president_name);
            $set('private_president_phone', $client->private_president_phone);
            $set('private_president_quota', $client->private_president_quota);

            Notification::make()
                ->title('Datos precargados')
                ->body('La información del cliente ha sido precargada. Puede editarla en el paso 2 si es necesario.')
                ->success()
                ->send();
        }
    }

    /**
     * Genera los documentos y procede al Wizard 2
     */
    public function generateDocumentsAndProceed(): void
    {
        // CRÍTICO: Obtener estado actual del formulario
        try {
            $formData = $this->form->getState();
            $this->data = array_merge($this->data ?? [], $formData);
        } catch (\Exception $e) {
        }

        // VALIDACIÓN: Verificar que el checkbox de confirmación esté marcado
        if (!($this->data['confirm_data_correct'] ?? false)) {
            Notification::make()
                ->title('⚠️ Confirmación Requerida')
                ->body('Debe marcar el checkbox para confirmar que ha revisado toda la información antes de generar los documentos.')
                ->warning()
                ->duration(5000)
                ->send();
            return;
        }

        if (!$this->agreementId) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró el convenio.')
                ->danger()
                ->send();
            return;
        }

        $agreement = Agreement::find($this->agreementId);

        if (!$agreement) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró el convenio.')
                ->danger()
                ->send();
            return;
        }

        // Actualizar estado inicial
        $agreement->update([
            'status' => 'documents_generating',
            'current_step' => 5,
            'current_wizard' => 2,
            'wizard2_current_step' => 1,
            'completion_percentage' => 100,
            'wizard_data' => $this->data,
            'can_return_to_wizard1' => false, // Ya no puede regresar al Wizard 1
        ]);

        try {
            // Generar documentos de forma síncrona
            $pdfService = app(PdfGenerationService::class);
            $documents = $pdfService->generateAllDocuments($agreement);

            Notification::make()
                ->title('📄 Documentos Generados')
                ->body('Se generaron exitosamente ' . count($documents) . ' documentos')
                ->success()
                ->duration(5000)
                ->send();

        } catch (\Exception $e) {
            // Si hay error, actualizar estado y mostrar error
            $agreement->update(['status' => 'error_generating_documents']);

            Notification::make()
                ->title('❌ Error al Generar Documentos')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();

            return; // No redirigir si hay error
        }

        // Limpiar la sesión del wizard antes de redirigir
        session()->forget('wizard_agreement_id');

        // Redirigir al Wizard 2 (nueva página migrada)
        $this->redirect("/admin/manage-documents/{$agreement->id}");
    }

    /**
     * Renderiza el resumen de datos del TITULAR
     */
    protected function renderHolderSummary(array $data): string
    {
        $html = '<div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">';

        if (!empty($data['holder_name'])) {
            $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">';
            $html .= '<div><strong class="text-blue-700">Nombre:</strong> ' . ($data['holder_name'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Email:</strong> ' . ($data['holder_email'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Teléfono:</strong> ' . ($data['holder_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Tel. Oficina:</strong> ' . ($data['holder_office_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">CURP:</strong> ' . ($data['holder_curp'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">RFC:</strong> ' . ($data['holder_rfc'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Estado Civil:</strong> ' . ($data['holder_civil_status'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Ocupación:</strong> ' . ($data['holder_occupation'] ?? 'N/A') . '</div>';

            // Domicilio del titular
            if (!empty($data['current_address'])) {
                $html .= '<div class="col-span-2 mt-3 pt-3 border-t border-blue-200">';
                $html .= '<h5 class="font-semibold text-blue-700 mb-2 flex items-center"><span class="mr-1">📍</span> Domicilio</h5>';

                $address = $data['current_address'];
                if (!empty($data['holder_house_number'])) {
                    $address .= ' #' . $data['holder_house_number'];
                }
                $html .= '<div class="mb-2"><strong class="text-blue-700">Dirección:</strong> ' . $address . '</div>';

                $html .= '<div class="grid grid-cols-2 gap-2">';
                if (!empty($data['neighborhood'])) {
                    $html .= '<div><strong class="text-blue-700">Colonia:</strong> ' . $data['neighborhood'] . '</div>';
                }
                if (!empty($data['postal_code'])) {
                    $html .= '<div><strong class="text-blue-700">C.P.:</strong> ' . $data['postal_code'] . '</div>';
                }
                if (!empty($data['municipality'])) {
                    $html .= '<div><strong class="text-blue-700">Municipio:</strong> ' . $data['municipality'] . '</div>';
                }
                if (!empty($data['state'])) {
                    $html .= '<div><strong class="text-blue-700">Estado:</strong> ' . $data['state'] . '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
        } else {
            $html .= '<div class="text-center text-gray-500 py-4">📝 No se capturó información del titular</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza el resumen de datos del CÓNYUGE/COACREDITADO
     */
    protected function renderSpouseSummary(array $data): string
    {
        $html = '<div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">';

        if (!empty($data['spouse_name'])) {
            $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">';
            $html .= '<div><strong class="text-green-700">Nombre:</strong> ' . ($data['spouse_name'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Email:</strong> ' . ($data['spouse_email'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Teléfono:</strong> ' . ($data['spouse_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Tel. Oficina:</strong> ' . ($data['spouse_office_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">CURP:</strong> ' . ($data['spouse_curp'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">RFC:</strong> ' . ($data['spouse_rfc'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Estado Civil:</strong> ' . ($data['spouse_civil_status'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Ocupación:</strong> ' . ($data['spouse_occupation'] ?? 'N/A') . '</div>';

            // Domicilio del cónyuge
            if (!empty($data['spouse_current_address'])) {
                $html .= '<div class="col-span-2 mt-3 pt-3 border-t border-green-200">';
                $html .= '<h5 class="font-semibold text-green-700 mb-2 flex items-center"><span class="mr-1">📍</span> Domicilio</h5>';

                $address = $data['spouse_current_address'];
                if (!empty($data['spouse_house_number'])) {
                    $address .= ' #' . $data['spouse_house_number'];
                }
                $html .= '<div class="mb-2"><strong class="text-green-700">Dirección:</strong> ' . $address . '</div>';

                $html .= '<div class="grid grid-cols-2 gap-2">';
                if (!empty($data['spouse_neighborhood'])) {
                    $html .= '<div><strong class="text-green-700">Colonia:</strong> ' . $data['spouse_neighborhood'] . '</div>';
                }
                if (!empty($data['spouse_postal_code'])) {
                    $html .= '<div><strong class="text-green-700">C.P.:</strong> ' . $data['spouse_postal_code'] . '</div>';
                }
                if (!empty($data['spouse_municipality'])) {
                    $html .= '<div><strong class="text-green-700">Municipio:</strong> ' . $data['spouse_municipality'] . '</div>';
                }
                if (!empty($data['spouse_state'])) {
                    $html .= '<div><strong class="text-green-700">Estado:</strong> ' . $data['spouse_state'] . '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
        } else {
            $html .= '<div class="text-center text-gray-500 py-4 italic">';
            $html .= '📝 No se capturó información del cónyuge / coacreditado';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza el resumen de datos de la propiedad
     */
    protected function renderPropertySummary(array $data): string
    {
        $html = '<div class="space-y-3">';

        $html .= '<div><strong>Domicilio:</strong> ' . ($data['domicilio_convenio'] ?? 'N/A') . '</div>';
        $html .= '<div><strong>Comunidad:</strong> ' . ($data['comunidad'] ?? 'N/A') . '</div>';
        $html .= '<div><strong>Tipo de Vivienda:</strong> ' . ($data['tipo_vivienda'] ?? 'N/A') . '</div>';
        $html .= '<div><strong>Prototipo:</strong> ' . ($data['prototipo'] ?? 'N/A') . '</div>';

        if (!empty($data['lote']) || !empty($data['manzana']) || !empty($data['etapa'])) {
            $html .= '<div><strong>Ubicación:</strong> ';
            if (!empty($data['lote'])) $html .= 'Lote ' . $data['lote'] . ' ';
            if (!empty($data['manzana'])) $html .= 'Manzana ' . $data['manzana'] . ' ';
            if (!empty($data['etapa'])) $html .= 'Etapa ' . $data['etapa'];
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza el resumen financiero
     */
    protected function renderFinancialSummary(array $data): string
    {
        $valorConvenio = floatval(str_replace(',', '', $data['valor_convenio'] ?? 0));
        $precioPromocion = floatval(str_replace(',', '', $data['precio_promocion'] ?? 0));
        $comisionTotal = floatval(str_replace(',', '', $data['comision_total_pagar'] ?? 0));
        $gananciaFinal = floatval(str_replace(',', '', $data['ganancia_final'] ?? 0));

        $html = '<div class="bg-gray-50 p-4 rounded-lg space-y-2">';
        $html .= '<div class="flex justify-between"><span><strong>Valor del Convenio:</strong></span><span class="font-bold text-blue-600">$' . number_format($valorConvenio, 2) . '</span></div>';
        $html .= '<div class="flex justify-between"><span><strong>Precio de Promoción:</strong></span><span class="font-bold text-green-600">$' . number_format($precioPromocion, 2) . '</span></div>';
        $html .= '<div class="flex justify-between"><span><strong>Comisión Total:</strong></span><span class="font-bold text-orange-600">$' . number_format($comisionTotal, 2) . '</span></div>';
        $html .= '<hr class="my-2">';
        $html .= '<div class="flex justify-between text-lg"><span><strong>Ganancia Final:</strong></span><span class="font-bold text-green-700">$' . number_format($gananciaFinal, 2) . '</span></div>';
        $html .= '</div>';

        return $html;
    }

    public function handleStepChange($step): void
    {

        if ($step >= 1) {
            $this->saveStepData($step);
        }
    }

    /**
     * Método que se ejecuta cuando se actualiza cualquier propiedad del componente
     */
    public function updated($propertyName)
    {
        // Si se actualiza el currentStep, guardar automáticamente
        if ($propertyName === 'currentStep' && $this->currentStep >= 1) {

            $this->saveStepData($this->currentStep);
        }
    }


    /**
     * Precarga los datos del cliente desde un objeto Client directamente en $this->data
     */
    private function preloadClientDataFromObject(Client $client): void
    {
        // Datos generales
        $this->data['xante_id'] = $client->xante_id;

        // Datos personales titular
        $this->data['holder_name'] = $client->name;
        $this->data['holder_birthdate'] = $client->birthdate;
        $this->data['holder_curp'] = $client->curp;
        $this->data['holder_rfc'] = $client->rfc;
        $this->data['holder_email'] = $client->email;
        $this->data['holder_phone'] = $client->phone;
        $this->data['holder_delivery_file'] = $client->delivery_file;
        $this->data['holder_civil_status'] = $client->civil_status;
        $this->data['holder_regime_type'] = $client->regime_type;
        $this->data['holder_occupation'] = $client->occupation;
        $this->data['holder_office_phone'] = $client->office_phone;
        $this->data['holder_additional_contact_phone'] = $client->additional_contact_phone;
        $this->data['current_address'] = $client->current_address;
        $this->data['holder_house_number'] = $client->house_number;
        $this->data['neighborhood'] = $client->neighborhood;
        $this->data['postal_code'] = $client->postal_code;
        $this->data['municipality'] = $client->municipality;
        $this->data['state'] = $client->state;

        // Datos cónyuge
        $this->data['spouse_name'] = $client->spouse_name;
        $this->data['spouse_birthdate'] = $client->spouse_birthdate;
        $this->data['spouse_curp'] = $client->spouse_curp;
        $this->data['spouse_rfc'] = $client->spouse_rfc;
        $this->data['spouse_email'] = $client->spouse_email;
        $this->data['spouse_phone'] = $client->spouse_phone;
        $this->data['spouse_delivery_file'] = $client->spouse_delivery_file;
        $this->data['spouse_civil_status'] = $client->spouse_civil_status;
        $this->data['spouse_regime_type'] = $client->spouse_regime_type;
        $this->data['spouse_occupation'] = $client->spouse_occupation;
        $this->data['spouse_office_phone'] = $client->spouse_office_phone;
        $this->data['spouse_additional_contact_phone'] = $client->spouse_additional_contact_phone;
        $this->data['spouse_current_address'] = $client->spouse_current_address;
        $this->data['spouse_house_number'] = $client->spouse_house_number;
        $this->data['spouse_neighborhood'] = $client->spouse_neighborhood;
        $this->data['spouse_postal_code'] = $client->spouse_postal_code;
        $this->data['spouse_municipality'] = $client->spouse_municipality;
        $this->data['spouse_state'] = $client->spouse_state;

        // Contactos AC/Presidente
        $this->data['ac_name'] = $client->ac_name;
        $this->data['ac_phone'] = $client->ac_phone;
        $this->data['ac_quota'] = $client->ac_quota;
        $this->data['private_president_name'] = $client->private_president_name;
        $this->data['private_president_phone'] = $client->private_president_phone;
        $this->data['private_president_quota'] = $client->private_president_quota;
    }

    public function submit(): void
    {
        // Método legacy - redirigir al nuevo método
        $this->generateDocumentsAndProceed();
    }

    /**
     * Verifica si el cliente actual tiene un pre-cálculo previo
     */
    protected function hasExistingProposal(): ?array
    {
        // Obtener el client_id del cliente seleccionado
        $clientId = $this->data['client_id'] ?? null;
        
        if (!$clientId) {
            return null;
        }
        
        $client = Client::find($clientId);
        
        if (!$client || !$client->xante_id) {
            return null;
        }
        
        // Buscar propuesta enlazada
        $proposal = Proposal::where('idxante', $client->xante_id)
            ->where('linked', true)
            ->latest()
            ->first();
        
        if (!$proposal) {
            return null;
        }
        
        // Retornar datos de la propuesta
        return [
            'exists' => true,
            'created_at' => $proposal->created_at,
            'valor_convenio' => $proposal->valor_convenio,
            'ganancia_final' => $proposal->ganancia_final,
            'data' => $proposal->data,
            'resumen' => $proposal->resumen,
        ];
    }

    /**
     * Precarga datos de propuesta existente si existe y los campos están vacíos
     */
    protected function preloadProposalDataIfExists(): void
    {
        $proposalInfo = $this->hasExistingProposal();
        
        if (!$proposalInfo || empty($proposalInfo['data'])) {
            return;
        }

        // Solo precargar si los campos calculadores están vacíos
        $shouldPreload = empty($this->data['valor_convenio']) || $this->data['valor_convenio'] == 0;
        
        if ($shouldPreload) {
            // Mezclar datos de la propuesta con datos actuales
            $this->data = array_merge($this->data, $proposalInfo['data']);
            
            Notification::make()
                ->title('🔄 Pre-cálculo Cargado')
                ->body('Se han precargado los valores de la cotización previa del cliente')
                ->info()
                ->duration(5000)
                ->send();
        }
    }
}
