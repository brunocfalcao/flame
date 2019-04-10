<?php

namespace Brunocfalcao\Flame;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class FlameServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->publishConfiguration();

        $this->loadConfigurationGroups();

        if (config('flame.demo.route')) {
            $this->loadDemoRoute();
        }

        $this->loadBladeDirectives();
    }

    public function register()
    {
        $this->commands([
            \Brunocfalcao\Flame\Commands\MakeFeatureCommand::class,
        ]);
    }

    protected function publishConfiguration()
    {
        $this->publishes([
            __DIR__.'/../config/flame.php' => config_path('flame.php'),
        ], 'flame-configuration');
    }

    protected function loadBladeDirectives()
    {
        Blade::directive(
            'twinkle',
            function ($expression) {
                return "<?php echo (new \\Brunocfalcao\\Flame\\Blade\\Directives\\Twinkle($expression))->render() ?>";
            }
        );
    }

    /**
     * Loads the flame configuration groups from your configuration file.
     * Each group will be located via the view hint given by the namespace name.
     *
     * @return void
     */
    protected function loadConfigurationGroups()
    {
        collect(config('flame.groups'))->each(function ($item, $key) {
            $this->loadViewsFrom(
                class_exists($item['path']) ? app($item['path'])() : $item['path'],
                $key
            );
        });
    }

    /**
     * Just a demo route on '/flame' :) .
     *
     * @return void
     */
    protected function loadDemoRoute()
    {
        Route::middleware(['web'])
             ->group(__DIR__.'/../routes/flame.php');
    }
}
