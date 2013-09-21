<?php

namespace Jboysen\Deploy;

use Illuminate\Support\ServiceProvider;

class DeployServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('jboysen/deploy');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    protected function registerCommands()
    {
        foreach (Commands\Base::commands() as $name => $desc)
        {
            if ($name === 'deploy')
            {
                $command = 'Menu';
                $name = 'command.deploy';
            }
            else
            {
                $command = ucfirst(str_replace('deploy:', '', $name));
                $name = 'command.deploy.' . $command;
            }
            $this->app[$name] = $this->app->share(function() use ($command)
                    {
                        $command = "\\Jboysen\\Deploy\\Commands\\" . $command;
                        return new $command;
                    });
            $this->commands($name);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
        );
    }

}