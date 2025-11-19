<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSearchHubspotRelations extends Command
{
    protected $signature = 'hubspot:deep-search-relations 
                            {--limit=50 : Número de deals a analizar}
                            {--only-accepted : Solo analizar deals aceptados}';
    
    protected $description = 'Búsqueda exhaustiva de relaciones Deal-Contact en HubSpot';

    private $token;
    private $baseUrl = 'https://api.hubapi.com';
    private $stats = [
        'total_deals' => 0,
        'with_associations' => 0,
        'with_contact_fields' => 0,
        'with_xante_id' => 0,
        'accepted_deals' => 0,
        'potential_strategies' => [],
    ];

    public function handle()
    {
        $this->token = config('hubspot.token');
        $limit = $this->option('limit');
        $onlyAccepted = $this->option('only-accepted');

        $this->info('🔍 BÚSQUEDA EXHAUSTIVA DE RELACIONES HUBSPOT');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Fase 1: Obtener TODOS los campos disponibles en Deals
        $this->info('📋 FASE 1: Analizando TODAS las propiedades disponibles en Deals...');
        $allProperties = $this->getAllDealProperties();
        $this->displayPropertyAnalysis($allProperties);

        // Fase 2: Obtener deals con TODAS las propiedades
        $this->info("\n📦 FASE 2: Analizando {$limit} deals con todas sus propiedades...");
        $deals = $this->fetchDealsWithAllProperties($limit, $onlyAccepted, $allProperties);
        
        if (empty($deals)) {
            $this->error('❌ No se encontraron deals para analizar');
            return 1;
        }

        $this->stats['total_deals'] = count($deals);
        $this->line("   ✅ {$this->stats['total_deals']} deals obtenidos");

        // Fase 3: Análisis profundo de cada deal
        $this->info("\n🔬 FASE 3: Análisis profundo de cada deal...");
        $this->newLine();
        
        $relationshipPatterns = [];
        $progressBar = $this->output->createProgressBar(count($deals));
        $progressBar->start();

        foreach ($deals as $index => $deal) {
            $dealId = $deal['id'];
            $properties = $deal['properties'] ?? [];
            
            // Análisis de este deal
            $analysis = $this->analyzeDeal($dealId, $properties);
            
            if (!empty($analysis['patterns'])) {
                $relationshipPatterns[$dealId] = $analysis;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);

        // Fase 4: Reportar hallazgos
        $this->info('📊 FASE 4: Reporte de Hallazgos');
        $this->info('═══════════════════════════════════════════════════════');
        $this->displayStatistics();
        $this->displayRelationshipPatterns($relationshipPatterns);
        $this->displayRecommendations($relationshipPatterns);

        return 0;
    }

    private function getAllDealProperties(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->get("{$this->baseUrl}/crm/v3/properties/deals");

            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }
        } catch (\Exception $e) {
            $this->error("Error obteniendo propiedades: {$e->getMessage()}");
        }
        
        return [];
    }

    private function displayPropertyAnalysis(array $properties): void
    {
        $contactRelated = [];
        $idFields = [];
        $emailFields = [];
        $phoneFields = [];
        $nameFields = [];

        foreach ($properties as $prop) {
            $name = $prop['name'];
            $label = $prop['label'] ?? '';
            $type = $prop['type'] ?? '';
            $lowerName = strtolower($name);
            $lowerLabel = strtolower($label);

            // Buscar campos relacionados con contactos
            if (str_contains($lowerName, 'contact') || str_contains($lowerLabel, 'contact')) {
                $contactRelated[] = ['name' => $name, 'label' => $label, 'type' => $type];
            }
            if (str_contains($lowerName, 'xante') || str_contains($lowerLabel, 'xante')) {
                $idFields[] = ['name' => $name, 'label' => $label, 'type' => $type];
            }
            if (str_contains($lowerName, 'email') || str_contains($lowerLabel, 'email')) {
                $emailFields[] = ['name' => $name, 'label' => $label, 'type' => $type];
            }
            if (str_contains($lowerName, 'phone') || str_contains($lowerName, 'telefono') || 
                str_contains($lowerLabel, 'phone') || str_contains($lowerLabel, 'teléfono')) {
                $phoneFields[] = ['name' => $name, 'label' => $label, 'type' => $type];
            }
            if (str_contains($lowerName, 'nombre') || str_contains($lowerName, 'name') ||
                str_contains($lowerName, 'cliente') || str_contains($lowerLabel, 'cliente')) {
                $nameFields[] = ['name' => $name, 'label' => $label, 'type' => $type];
            }
        }

        $this->displayFieldGroup('Campos relacionados con CONTACT', $contactRelated);
        $this->displayFieldGroup('Campos con IDs (xante, etc)', $idFields);
        $this->displayFieldGroup('Campos de EMAIL', $emailFields);
        $this->displayFieldGroup('Campos de TELÉFONO', $phoneFields);
        $this->displayFieldGroup('Campos de NOMBRE/CLIENTE', $nameFields);
    }

    private function displayFieldGroup(string $title, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $this->newLine();
        $this->info("   🔹 {$title}: " . count($fields) . " encontrados");
        foreach ($fields as $field) {
            $this->line("      • {$field['name']} ({$field['type']}) - \"{$field['label']}\"");
        }
    }

    private function fetchDealsWithAllProperties(int $limit, bool $onlyAccepted, array $allProperties): array
    {
        $propertyNames = array_map(fn($p) => $p['name'], $allProperties);
        
        $filters = [];
        if ($onlyAccepted) {
            $filters = [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'propertyName' => 'estatus_de_convenio',
                                'operator' => 'EQ',
                                'value' => 'Aceptado'
                            ]
                        ]
                    ]
                ]
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/crm/v3/objects/deals/search", array_merge([
                'properties' => $propertyNames,
                'limit' => $limit,
            ], $filters));

            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }
        } catch (\Exception $e) {
            $this->error("Error obteniendo deals: {$e->getMessage()}");
        }

        return [];
    }

    private function analyzeDeal(string $dealId, array $properties): array
    {
        $patterns = [];

        // Verificar estatus
        $estatus = $properties['estatus_de_convenio'] ?? null;
        if ($estatus === 'Aceptado') {
            $this->stats['accepted_deals']++;
        }

        // Buscar campos con valores que podrían ser IDs de contactos
        foreach ($properties as $key => $value) {
            if (empty($value)) continue;

            $lowerKey = strtolower($key);
            
            // Buscar xante_id
            if (str_contains($lowerKey, 'xante')) {
                $patterns['xante_id'] = ['field' => $key, 'value' => $value];
                $this->stats['with_xante_id']++;
            }

            // Buscar emails
            if (str_contains($lowerKey, 'email') && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $patterns['email'] = ['field' => $key, 'value' => $value];
            }

            // Buscar teléfonos
            if ((str_contains($lowerKey, 'phone') || str_contains($lowerKey, 'telefono')) && 
                preg_match('/[\d\s\+\-\(\)]{7,}/', $value)) {
                $patterns['phone'] = ['field' => $key, 'value' => $value];
            }

            // Buscar IDs de contactos (números que podrían ser IDs)
            if (str_contains($lowerKey, 'contact') && is_numeric($value)) {
                $patterns['contact_id'] = ['field' => $key, 'value' => $value];
            }
        }

        // Verificar asociaciones (API v3 y v4)
        $associations = $this->checkAssociations($dealId);
        if (!empty($associations)) {
            $patterns['associations'] = $associations;
            $this->stats['with_associations']++;
        }

        if (!empty($patterns)) {
            $this->stats['with_contact_fields']++;
        }

        return ['properties' => $properties, 'patterns' => $patterns, 'estatus' => $estatus];
    }

    private function checkAssociations(string $dealId): array
    {
        $associations = [];

        // API v3
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->get("{$this->baseUrl}/crm/v3/objects/deals/{$dealId}/associations/contacts");

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                if (!empty($results)) {
                    $associations['v3'] = $results;
                }
            }
        } catch (\Exception $e) {
            // Continuar con v4
        }

        // API v4
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
            ])->get("{$this->baseUrl}/crm/v4/objects/deals/{$dealId}/associations/contacts");

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                if (!empty($results)) {
                    $associations['v4'] = $results;
                }
            }
        } catch (\Exception $e) {
            // No hacer nada
        }

        return $associations;
    }

    private function displayStatistics(): void
    {
        $this->newLine();
        $this->info('📈 Estadísticas Generales:');
        $this->table(
            ['Métrica', 'Cantidad', 'Porcentaje'],
            [
                ['Total de Deals analizados', $this->stats['total_deals'], '100%'],
                ['Deals con estatus "Aceptado"', $this->stats['accepted_deals'], 
                    $this->getPercentage($this->stats['accepted_deals'], $this->stats['total_deals'])],
                ['Deals con asociaciones Contact', $this->stats['with_associations'], 
                    $this->getPercentage($this->stats['with_associations'], $this->stats['total_deals'])],
                ['Deals con campos de contacto', $this->stats['with_contact_fields'], 
                    $this->getPercentage($this->stats['with_contact_fields'], $this->stats['total_deals'])],
                ['Deals con xante_id', $this->stats['with_xante_id'], 
                    $this->getPercentage($this->stats['with_xante_id'], $this->stats['total_deals'])],
            ]
        );
    }

    private function displayRelationshipPatterns(array $patterns): void
    {
        if (empty($patterns)) {
            $this->warn("\n⚠️  No se encontraron patrones de relación en ningún deal");
            return;
        }

        $this->info("\n✅ PATRONES DE RELACIÓN ENCONTRADOS:");
        $this->info('═══════════════════════════════════════════════════════');

        $strategyCount = [
            'associations' => 0,
            'xante_id' => 0,
            'email' => 0,
            'phone' => 0,
            'contact_id' => 0,
        ];

        foreach ($patterns as $dealId => $analysis) {
            $dealPatterns = $analysis['patterns'];
            $estatus = $analysis['estatus'] ?? 'N/A';

            foreach (array_keys($strategyCount) as $strategy) {
                if (isset($dealPatterns[$strategy])) {
                    $strategyCount[$strategy]++;
                }
            }
        }

        $this->newLine();
        $this->info('🎯 Estrategias Disponibles (por frecuencia):');
        arsort($strategyCount);
        
        foreach ($strategyCount as $strategy => $count) {
            if ($count > 0) {
                $percentage = $this->getPercentage($count, count($patterns));
                $icon = $count > count($patterns) / 2 ? '✅' : '⚠️ ';
                $this->line("   {$icon} {$strategy}: {$count} deals ({$percentage})");
            }
        }

        // Mostrar ejemplos detallados
        $this->newLine();
        $this->info('📋 Ejemplos Detallados:');
        $shown = 0;
        foreach ($patterns as $dealId => $analysis) {
            if ($shown >= 3) break;
            
            $this->newLine();
            $this->line("   Deal ID: {$dealId}");
            $this->line("   Estatus: {$analysis['estatus']}");
            
            foreach ($analysis['patterns'] as $patternType => $data) {
                if ($patternType === 'associations') {
                    $contactIds = [];
                    foreach ($data as $apiVersion => $assocs) {
                        foreach ($assocs as $assoc) {
                            $contactIds[] = $assoc['id'] ?? $assoc['toObjectId'] ?? 'unknown';
                        }
                    }
                    $this->line("   → {$patternType}: " . implode(', ', array_unique($contactIds)));
                } else {
                    $this->line("   → {$patternType}: {$data['field']} = {$data['value']}");
                }
            }
            $shown++;
        }
    }

    private function displayRecommendations(array $patterns): void
    {
        $this->info("\n💡 RECOMENDACIONES:");
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        if ($this->stats['with_associations'] > 0) {
            $this->info('✅ ESTRATEGIA RECOMENDADA: Asociaciones Directas');
            $percentage = $this->getPercentage($this->stats['with_associations'], $this->stats['total_deals']);
            $this->line("   {$this->stats['with_associations']} deals ({$percentage}) tienen asociaciones con Contacts");
            $this->line('   Implementación:');
            $this->line('   1. Usar endpoint /crm/v3/objects/deals/{id}/associations/contacts');
            $this->line('   2. Obtener Contact asociado');
            $this->line('   3. Extraer xante_id del Contact');
            $this->newLine();
        }

        if ($this->stats['with_xante_id'] > 0) {
            $this->info('✅ ESTRATEGIA ALTERNATIVA: Campo xante_id en Deal');
            $percentage = $this->getPercentage($this->stats['with_xante_id'], $this->stats['total_deals']);
            $this->line("   {$this->stats['with_xante_id']} deals ({$percentage}) tienen campo xante_id");
            $this->line('   Implementación:');
            $this->line('   1. Extraer xante_id directamente del Deal');
            $this->line('   2. Buscar Contact en HubSpot por xante_id');
            $this->line('   3. Crear/actualizar Client');
            $this->newLine();
        }

        if ($this->stats['with_contact_fields'] > 0 && 
            $this->stats['with_associations'] === 0 && 
            $this->stats['with_xante_id'] === 0) {
            $this->warn('⚠️  ESTRATEGIA DE RESPALDO: Búsqueda por Email/Teléfono');
            $this->line('   Algunos deals tienen emails o teléfonos');
            $this->line('   Implementación:');
            $this->line('   1. Extraer email/teléfono del Deal');
            $this->line('   2. Buscar Contact en HubSpot por ese criterio');
            $this->line('   3. Verificar xante_id del Contact encontrado');
            $this->newLine();
        }

        if ($this->stats['with_associations'] === 0 && 
            $this->stats['with_xante_id'] === 0 && 
            $this->stats['with_contact_fields'] === 0) {
            $this->error('❌ SIN ESTRATEGIA VIABLE ENCONTRADA');
            $this->line('   Ningún deal analizado tiene información de contacto');
            $this->line('   Recomendaciones:');
            $this->line('   1. Verificar configuración en HubSpot');
            $this->line('   2. Analizar más deals (--limit=100 o más)');
            $this->line('   3. Considerar solo deals con estatus Aceptado (--only-accepted)');
            $this->line('   4. Configurar asociaciones Deal→Contact en HubSpot');
        }
    }

    private function getPercentage(int $part, int $total): string
    {
        if ($total === 0) return '0%';
        return round(($part / $total) * 100, 1) . '%';
    }
}
