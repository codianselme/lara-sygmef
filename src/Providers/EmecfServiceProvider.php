<?php

namespace Codianselme\LaraSygmef\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Codianselme\LaraSygmef\Services\EmecfService;

class EmecfServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Fusionner la configuration
        $this->mergeConfigFrom(
            __DIR__."/../../config/emecf.php",
            "emecf"
        );

        // Enregistrer le service e-MECeF
        $this->app->singleton(EmecfService::class, function ($app) {
            return new EmecfService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publier la configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__."/../../config/emecf.php" => config_path("emecf.php"),
            ], "emecf-config");

            // Publier les migrations
            $this->publishes([
                __DIR__."/../../database/migrations" => database_path("migrations"),
            ], "emecf-migrations");

            // Publier les routes API
            $this->publishes([
                __DIR__."/../../routes/emecf.php" => base_path("routes/emecf.php"),
            ], "emecf-routes");
            
            // Publier les routes Dashboard
            $this->publishes([
                __DIR__."/../../routes/dashboard.php" => base_path("routes/dashboard.php"),
            ], "emecf-dashboard");
        }

        // Charger les routes API si elles existent
        if (file_exists(base_path("routes/emecf.php"))) {
            $this->loadRoutesFrom(base_path("routes/emecf.php"));
        } else {
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(__DIR__.'/../../routes/emecf.php');
        }
        
        // Charger les routes Dashboard
        if (file_exists(base_path("routes/dashboard.php"))) {
            $this->loadRoutesFrom(base_path("routes/dashboard.php"));
        } else {
            $this->loadRoutesFrom(__DIR__.'/../../routes/dashboard.php');
        }
        
        // Charger les vues
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'emecf');
        
        // Charger les migrations automatiquement
        $this->loadMigrationsFrom(__DIR__."/../../database/migrations");
    }
}
