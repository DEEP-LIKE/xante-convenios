<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'xante_id' => 'XNT' . $this->faker->unique()->numberBetween(1000, 9999),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'birthdate' => $this->faker->date(),
            'curp' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}'),
            'rfc' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'delivery_file' => $this->faker->word(),
            'civil_status' => $this->faker->randomElement(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre']),
            'regime_type' => $this->faker->randomElement(['Bienes Separados', 'Sociedad Conyugal']),
            'occupation' => $this->faker->jobTitle(),
            'office_phone' => $this->faker->phoneNumber(),
            'additional_contact_phone' => $this->faker->phoneNumber(),
            'current_address' => $this->faker->streetAddress(),
            'neighborhood' => $this->faker->citySuffix(),
            'postal_code' => $this->faker->postcode(),
            'municipality' => $this->faker->city(),
            'state' => $this->faker->state(),
            
            // Datos del cónyuge (opcionales)
            'spouse_name' => $this->faker->optional()->name(),
            'spouse_birthdate' => $this->faker->optional()->date(),
            'spouse_curp' => $this->faker->optional()->regexify('[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}'),
            'spouse_rfc' => $this->faker->optional()->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'spouse_email' => $this->faker->optional()->safeEmail(),
            'spouse_phone' => $this->faker->optional()->phoneNumber(),
            'spouse_delivery_file' => $this->faker->optional()->word(),
            'spouse_civil_status' => $this->faker->optional()->randomElement(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre']),
            'spouse_regime_type' => $this->faker->optional()->randomElement(['Bienes Separados', 'Sociedad Conyugal']),
            'spouse_occupation' => $this->faker->optional()->jobTitle(),
            'spouse_office_phone' => $this->faker->optional()->phoneNumber(),
            'spouse_additional_contact_phone' => $this->faker->optional()->phoneNumber(),
            'spouse_current_address' => $this->faker->optional()->streetAddress(),
            'spouse_neighborhood' => $this->faker->optional()->citySuffix(),
            'spouse_postal_code' => $this->faker->optional()->postcode(),
            'spouse_municipality' => $this->faker->optional()->city(),
            'spouse_state' => $this->faker->optional()->state(),
            
            // Contactos AC/Presidente
            'ac_name' => $this->faker->optional()->name(),
            'ac_phone' => $this->faker->optional()->phoneNumber(),
            'ac_quota' => $this->faker->optional()->numberBetween(1000, 5000),
            'private_president_name' => $this->faker->optional()->name(),
            'private_president_phone' => $this->faker->optional()->phoneNumber(),
            'private_president_quota' => $this->faker->optional()->numberBetween(1000, 5000),
        ];
    }

    /**
     * Indicate that the client has complete spouse information.
     */
    public function withSpouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'spouse_name' => $this->faker->name(),
            'spouse_birthdate' => $this->faker->date(),
            'spouse_curp' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}'),
            'spouse_rfc' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'spouse_email' => $this->faker->safeEmail(),
            'spouse_phone' => $this->faker->phoneNumber(),
            'spouse_civil_status' => 'casado',
            'spouse_occupation' => $this->faker->jobTitle(),
            'spouse_current_address' => $attributes['current_address'],
            'spouse_neighborhood' => $attributes['neighborhood'],
            'spouse_postal_code' => $attributes['postal_code'],
            'spouse_municipality' => $attributes['municipality'],
            'spouse_state' => $attributes['state'],
        ]);
    }

    /**
     * Indicate that the client has AC and President contacts.
     */
    public function withContacts(): static
    {
        return $this->state(fn (array $attributes) => [
            'ac_name' => $this->faker->name(),
            'ac_phone' => $this->faker->phoneNumber(),
            'ac_quota' => $this->faker->numberBetween(2000, 5000),
            'private_president_name' => $this->faker->name(),
            'private_president_phone' => $this->faker->phoneNumber(),
            'private_president_quota' => $this->faker->numberBetween(3000, 8000),
        ]);
    }
}
