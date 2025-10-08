import preset from './vendor/filament/support/tailwind.config.preset'
import defaultTheme from 'tailwindcss/defaultTheme'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                // Franie como fuente principal (sans) - Identidad Xante
                sans: ['Franie', ...defaultTheme.fontFamily.sans],
                // Bitcheese como fuente para títulos destacados (display)
                display: ['Bitcheese', 'sans-serif'],
            },
            colors: {
                // Paleta de colores corporativos de Xante
                custom: {
                    'primary': '#6C2582',    // Morado Oscuro Xante
                    'success': '#BDCE0F',    // Verde Lima Xante
                    'warning': '#FFD729',    // Amarillo Xante
                    'danger': '#D63B8E',     // Rosa Xante
                    'info': '#7C4794',       // Morado Medio Xante
                },
                xante: {
                    // Morados
                    'purple': '#6C2582',           // Morado Principal
                    'purple-mid': '#7C4794',       // Morado Medio
                    'deep-purple': '#62257D',      // Morado Profundo
                    'dark-blue': '#342970',        // Azul Oscuro (Texto)
                    
                    // Verdes
                    'green': '#BDCE0F',            // Verde Principal
                    'green-light': '#C9D534',      // Verde Claro
                    
                    // Acentos
                    'yellow': '#FFD729',           // Amarillo
                    'magenta': '#AD167F',          // Magenta
                    'pink': '#D63B8E',             // Rosa
                },
            },
        },
    },
}
