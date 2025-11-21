<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\Client;
use App\Models\Agreement;
use Illuminate\Support\Facades\Auth;

class StepOneSchema
{
    public static function make($page): Step
    {
        return Step::make('Identificación')
            ->description('Búsqueda y selección del cliente')
            ->icon('heroicon-o-magnifying-glass')
            ->afterValidation(function ($state) use ($page) {
                // Si no tenemos un ID de convenio, es la primera vez que pasamos este paso.
                if (!$page->agreementId) {
                    $client = Client::find($state['client_id']);

                    // Crear el convenio por primera vez
                    $agreement = Agreement::create([
                        'status' => 'expediente_incompleto',
                        'current_step' => 1,
                        'created_by' => Auth::id(),
                        'client_id' => $state['client_id'],
                        'client_xante_id' => $client ? $client->xante_id : null,
                    ]);

                    $page->agreementId = $agreement->id;

                    // Guardar en sesión para persistencia en recargas
                    session(['wizard_agreement_id' => $page->agreementId]);
                }

                // Guardar los datos del paso actual
                $page->saveStepData(1);
            })
            ->schema([
                Select::make('client_id')
                    ->label('Cliente Seleccionado')
                    ->placeholder('Busque por nombre o ID Xante...')
                    ->searchable()
                    ->options(function () {
                        // Mostrar los 100 clientes más recientes al abrir el dropdown
                        return Client::query()
                            ->orderBy('hubspot_synced_at', 'asc')
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(fn ($client) => [$client->id => $client->name . ' — ' . $client->xante_id])
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        // Búsqueda dinámica cuando el usuario escribe
                        return Client::query()
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('xante_id', 'like', "%{$search}%");
                            })
                            ->orderBy('hubspot_synced_at', 'asc')
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(fn ($client) => [$client->id => $client->name . ' — ' . $client->xante_id])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $client = Client::find($value);
                        return $client ? $client->name . ' — ' . $client->xante_id : '';
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) use ($page) {
                        if ($state) {
                            $page->preloadClientData($state, $set);
                        }
                    })
                    ->suffixAction(
                        Action::make('sync_search')
                            ->label('Sincronizar')
                            ->icon('heroicon-o-arrow-path')
                            ->color('success')
                            ->action(function () {
                                Notification::make()
                                    ->title('Sincronización Iniciada')
                                    ->body('La sincronización con Hubspot de la fuente de datos externa ha comenzado.')
                                    ->warning()
                                    ->icon('heroicon-o-arrow-path')
                                    ->send();
                            })
                    ),
            ]);
    }
}
