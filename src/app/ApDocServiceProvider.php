<?php
namespace Laililmahfud\ApDoc;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ApDocServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Route::middlewareGroup('apdoc', config('apdoc.middleware', []));

        $this->registerRoutes();
        $this->publisher();
        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'apdoc');

    }
    

  
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/apdoc.php', 'apdoc');
        require __DIR__ . '/Helpers/helpers.php';
    }


    protected function publisher(){
        $this->publishes([
            __DIR__.'/../config/apdoc.php' => config_path('apdoc.php'),
        ]);
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../resources/routes/apdoc.php', 'apdoc');
        });
    }
    protected function routeConfiguration()
    {
        return [
            'domain' => config('apdoc.domain', null),
            'prefix' => config('apdoc.path'),
            'middleware' => 'apdoc',
            'as' => 'apdoc.',
        ];
    }
}
