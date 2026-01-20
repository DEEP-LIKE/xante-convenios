<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AgreementsChart;
use App\Filament\Widgets\ProposalStatsWidget;
use App\Filament\Widgets\StatsOverview;
use App\Http\Middleware\SetSpanishLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Xante')
            ->favicon(asset('favicon/favicon.ico'))
            ->brandLogo(fn () => view('filament.brand.logo'))
            ->darkModeBrandLogo(fn () => view('filament.brand.logo-dark'))
            ->brandLogoHeight('5rem')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)

            // Paleta de colores corporativos de Xante
            ->colors([
                'primary' => Color::hex('#6C2582'),      // Morado Oscuro Xante (Primary)
                'success' => Color::hex('#BDCE0F'),      // Verde Lima Xante (Success)
                'warning' => Color::hex('#FFD729'),      // Amarillo Xante
                'danger' => Color::hex('#D63B8E'),       // Rosa Xante
                'info' => Color::hex('#7C4794'),         // Morado Medio Xante
                'gray' => Color::hex('#342970'),         // Azul Violeta Xante (Texto base)
            ])

            // Usar fuente del sistema por ahora para evitar problemas
            // ->font('Franie')
            // ->viteTheme('resources/css/filament/admin/theme.css') // Comentado para evitar conflictos

            // Habilitar notificaciones de base de datos (Necesario para recibir avisos de Jobs en background)
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s') // Revisar cada 15 segundos

            ->navigationGroups([
                NavigationGroup::make('Configuraciones')->collapsed(true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->pages([
                Dashboard::class,
                \App\Filament\Pages\CreateAgreementWizard::class,
                \App\Filament\Pages\QuoteCalculatorPage::class,
                \App\Filament\Pages\ManageDocuments::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ProposalStatsWidget::class,
                StatsOverview::class,
                AgreementsChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetSpanishLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
