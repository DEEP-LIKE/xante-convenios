<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio para búsqueda de clientes
 * 
 * Responsabilidades:
 * - Buscar clientes por diferentes criterios
 * - Obtener cliente por ID
 */
class ClientSearchService
{
    /**
     * Busca clientes por término de búsqueda
     * 
     * Busca en: nombre, email, xante_id
     */
    public function search(string $term, int $limit = 10): Collection
    {
        if (empty($term)) {
            return new Collection();
        }

        return Client::where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('xante_id', 'like', "%{$term}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Encuentra un cliente por ID
     */
    public function findById(int $id): ?Client
    {
        return Client::with('spouse')->find($id);
    }

    /**
     * Obtiene el primer cliente que coincida con el término de búsqueda
     */
    public function findFirst(string $term): ?Client
    {
        $results = $this->search($term, 1);
        
        return $results->first();
    }

    /**
     * Busca y retorna opciones formateadas para un Select
     */
    public function getOptionsForSelect(): array
    {
        return Client::query()
            ->selectRaw("id, CONCAT(name, ' — ', xante_id) as display_name")
            ->pluck('display_name', 'id')
            ->toArray();
    }
}
