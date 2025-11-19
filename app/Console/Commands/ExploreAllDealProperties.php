<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ExploreAllDealProperties extends Command
{
    protected $signature = 'hubspot:explore-all-properties {--deal-id=}';
    protected $description = 'Explorar TODAS las propiedades de un Deal específico';

    public function handle()
    {
        $this->info('🔍 Explorando TODAS las propiedades del Deal');
        $this->line('═══════════════════════════════════════════════════');
        
        $token = config('hubspot.token');
        $dealId = $this->option('deal-id');
        
        if (!$token) {
            $this->error('❌ HUBSPOT_TOKEN no configurado');
            return 1;
        }

        try {
            // Obtener primer Deal si no se especifica ID
            if (!$dealId) {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                ])->get('https://api.hubapi.com/crm/v3/objects/deals', ['limit' => 1]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $dealId = $data['results'][0]['id'] ?? null;
                }
            }

            if (!$dealId) {
                $this->error('❌ No se pudo obtener Deal ID');
                return 1;
            }

            $this->info("📌 Deal ID: {$dealId}");
            $this->newLine();

            // Obtener Deal con TODAS las propiedades (sin filtro)
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
            ])->get("https://api.hubapi.com/crm/v3/objects/deals/{$dealId}");

            if (!$response->successful()) {
                $this->error('❌ Error obteniendo Deal: ' . $response->status());
                return 1;
            }

            $deal = $response->json();
            $properties = $deal['properties'] ?? [];

            $this->info("📊 Total de propiedades: " . count($properties));
            $this->newLine();

            // Agrupar propiedades por categorías
            $categories = [
                'xante' => [],
                'contact' => [],
                'cliente' => [],
                'email' => [],
                'telefono' => [],
                'nombre' => [],
                'estatus' => [],
                'fecha' => [],
                'other' => []
            ];

            foreach ($properties as $key => $value) {
                $lowerKey = strtolower($key);
                $categorized = false;

                foreach (['xante', 'contact', 'cliente', 'email', 'telefono', 'nombre', 'estatus', 'fecha'] as $category) {
                    if (str_contains($lowerKey, $category)) {
                        $categories[$category][$key] = $value;
                        $categorized = true;
                        break;
                    }
                }

                if (!$categorized) {
                    $categories['other'][$key] = $value;
                }
            }

            // Mostrar propiedades por categoría
            foreach ($categories as $category => $props) {
                if (empty($props)) continue;
                
                $this->info("📁 Categoría: " . strtoupper($category) . " (" . count($props) . " propiedades)");
                $this->line('─────────────────────────────────────────────────');
                
                foreach ($props as $key => $value) {
                    $displayValue = $value;
                    if (is_string($value)) {
                        $displayValue = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
                    } else {
                        $displayValue = json_encode($value);
                    }
                    
                    $this->line("  • {$key}: {$displayValue}");
                }
                
                $this->newLine();
            }

            // Resumen de campos críticos
            $this->info("🎯 CAMPOS CRÍTICOS PARA SINCRONIZACIÓN");
            $this->line('═══════════════════════════════════════════════════');
            
            $criticalFields = [
                'xante_id',
                'cliente_id',
                'contact_id',
                'email',
                'email_cliente',
                'telefono',
                'telefono_cliente',
                'nombre_cliente',
                'nombre_completo',
                'estatus_de_convenio',
                'hs_all_associated_contact_ids'
            ];

            $found = [];
            $notFound = [];

            foreach ($criticalFields as $field) {
                if (isset($properties[$field])) {
                    $found[$field] = $properties[$field];
                } else {
                    $notFound[] = $field;
                }
            }

            if (!empty($found)) {
                $this->info("✅ Campos encontrados:");
                foreach ($found as $key => $value) {
                    $this->line("   • {$key}: {$value}");
                }
            }

            if (!empty($notFound)) {
                $this->newLine();
                $this->warn("⚠️  Campos NO encontrados:");
                foreach ($notFound as $field) {
                    $this->line("   • {$field}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
