<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            [
                'address' => 'Av. Santa Fe 1234, Piso 15, Depto 1501, Col. Santa Fe, CDMX',
                'community' => 'Santa Fe',
                'property_type' => 'departamento',
                'value' => 4500000.00,
                'mortgage_amount' => 3200000.00,
            ],
            [
                'address' => 'Calle Polanco 567, Col. Polanco V Sección, CDMX',
                'community' => 'Polanco',
                'property_type' => 'casa',
                'value' => 8500000.00,
                'mortgage_amount' => null,
            ],
            [
                'address' => 'Av. Universidad 890, Col. Narvarte, CDMX',
                'community' => 'Narvarte',
                'property_type' => 'departamento',
                'value' => 2800000.00,
                'mortgage_amount' => 1900000.00,
            ],
            [
                'address' => 'Blvd. Interlomas 345, Fracc. Interlomas, Estado de México',
                'community' => 'Interlomas',
                'property_type' => 'casa',
                'value' => 12000000.00,
                'mortgage_amount' => 8500000.00,
            ],
            [
                'address' => 'Av. Revolución 678, Col. San Ángel, CDMX',
                'community' => 'San Ángel',
                'property_type' => 'condominio',
                'value' => 6200000.00,
                'mortgage_amount' => null,
            ],
        ];

        foreach ($properties as $property) {
            Property::create($property);
        }
    }
}
