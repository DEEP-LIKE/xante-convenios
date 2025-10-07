# Componentes Xante - Guía de Uso

## Colores Corporativos Aplicados

Los siguientes colores corporativos de Xante están configurados en Filament:

- **Primario**: `#BDCE0F` (Verde Xante)
- **Secundario**: `#6C2582` (Morado Xante)  
- **Éxito**: `#C9D534` (Verde claro Xante)
- **Advertencia**: `#FFD729` (Amarillo Xante)
- **Peligro**: `#D63B8E` (Rosa Xante)
- **Información**: `#62257D` (Morado profundo Xante)

## Componentes Reutilizables

### 1. Botón Xante

```blade
<!-- Botón primario -->
<x-xante-button variant="primary">
    Guardar
</x-xante-button>

<!-- Botón secundario grande -->
<x-xante-button variant="secondary" size="lg">
    Cancelar
</x-xante-button>

<!-- Botón como enlace -->
<x-xante-button variant="success" href="/ruta">
    Ver más
</x-xante-button>
```

**Variantes**: primary (primario), secondary (secundario), success (éxito), warning (advertencia), danger (peligro), info (información)
**Tamaños**: sm (pequeño), md (mediano), lg (grande)

### 2. Xante Card

```blade
<!-- Card básica -->
<x-xante-card>
    <h3>Título</h3>
    <p>Contenido de la tarjeta</p>
</x-xante-card>

<!-- Card con borde primario -->
<x-xante-card variant="primary" padding="lg">
    <h3>Tarjeta destacada</h3>
</x-xante-card>
```

**Variantes**: default, primary, secondary
**Padding**: sm, md, lg

### 3. Xante Badge

```blade
<!-- Badge de estado -->
<x-xante-badge variant="success">
    Activo
</x-xante-badge>

<!-- Badge de advertencia -->
<x-xante-badge variant="warning" size="lg">
    Pendiente
</x-xante-badge>
```

**Variantes**: primary, secondary, success, warning, danger, info
**Tamaños**: sm, md, lg

### 4. Xante Alert

```blade
<!-- Alerta de éxito -->
<x-xante-alert variant="success">
    ¡Operación completada exitosamente!
</x-xante-alert>

<!-- Alerta dismissible -->
<x-xante-alert variant="warning" :dismissible="true">
    Revisa la información antes de continuar.
</x-xante-alert>
```

**Variantes**: success, warning, danger, info

### 5. Xante Input

```blade
<!-- Input básico -->
<x-xante-input 
    label="Nombre completo"
    name="name"
    :required="true"
    hint="Ingresa tu nombre completo"
/>

<!-- Input con error -->
<x-xante-input 
    label="Email"
    type="email"
    name="email"
    error="El email es requerido"
/>
```

### 6. Xante Logo

```blade
<!-- Logo normal -->
<x-xante-logo />

<!-- Logo blanco grande -->
<x-xante-logo variant="white" size="xl" />

<!-- Logo pequeño -->
<x-xante-logo size="sm" />
```

**Variantes**: default, white
**Tamaños**: sm, md, lg, xl

## Ejemplo de Uso Completo

```blade
<x-xante-card variant="primary" padding="lg">
    <div class="flex items-center justify-between mb-6">
        <x-xante-logo size="md" />
        <x-xante-badge variant="success">Activo</x-xante-badge>
    </div>
    
    <h2 class="text-xl font-semibold mb-4">Formulario de Convenio</h2>
    
    <x-xante-alert variant="info" class="mb-4">
        Complete todos los campos requeridos.
    </x-xante-alert>
    
    <div class="space-y-4">
        <x-xante-input 
            label="Nombre del cliente"
            name="client_name"
            :required="true"
        />
        
        <x-xante-input 
            label="Email"
            type="email"
            name="email"
            :required="true"
        />
    </div>
    
    <div class="flex gap-3 mt-6">
        <x-xante-button variant="primary" size="lg">
            Guardar Convenio
        </x-xante-button>
        
        <x-xante-button variant="secondary">
            Cancelar
        </x-xante-button>
    </div>
</x-xante-card>
```

## Notas Importantes

- Todos los componentes usan los colores corporativos de Xante
- Son completamente reutilizables en cualquier parte de la aplicación
- Mantienen la estructura actual de Filament sin alterarla
- Se integran perfectamente con Tailwind CSS
- Incluyen estados hover, focus y transiciones suaves
