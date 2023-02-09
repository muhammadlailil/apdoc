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
        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'apdoc');

    }
    

    /**
     * Register the API doc commands.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/apdoc.php', 'apdoc');
        require __DIR__ . '/Helpers/helpers.php';
    }

    /**
     * Get the iDoc route group configuration array.
     *
     * @return array
     */
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../resources/routes/apdoc.php', 'apdoc');
        });
    }

    /**
     * Get the iDoc route group configuration array.
     *
     * @return array
     */
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
