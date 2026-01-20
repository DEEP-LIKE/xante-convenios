<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ConfigurationCalculator extends Model
{
    protected $table = 'configurations';

    protected $fillable = [
        'key',
        'description',
        'value',
        'type',
    ];

    // Método para obtener el valor parseado según el tipo
    public function getParsedValueAttribute()
    {
        return match ($this->type) {
            'number' => (int) $this->value,
            'decimal' => (float) $this->value,
            'boolean' => (bool) $this->value,
            default => $this->value,
        };
    }

    // Método estático para obtener una configuración por clave
    public static function get(string $key, $default = null)
    {
        $config = Cache::remember("config.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        return $config ? $config->parsed_value : $default;
    }

    // Método estático para establecer una configuración
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("config.{$key}");
    }

    // Limpiar cache cuando se actualiza
    protected static function booted()
    {
        static::saved(function ($configuration) {
            Cache::forget("config.{$configuration->key}");
        });

        static::deleted(function ($configuration) {
            Cache::forget("config.{$configuration->key}");
        });
    }
}
