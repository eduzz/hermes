<?php

namespace Eduzz\Hermes;

use Eduzz\Hermes\Hermes;
use Illuminate\Support\ServiceProvider;

class HermesLaravelServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->publishes(
            [
                __DIR__ . '/Config/hermes.php' => $this->getConfigPath('hermes.php'),
            ], 'config'
        );
    }

    public function register()
    {
        $this->app->bind(
            'Eduzz\Hermes\Hermes', function ($app) {
                $hermes = new Hermes();

                $config = config('hermes.connection');
                $name = config('app.name');

                if (!(array_key_exists('connection_name', $config)) || empty($config['connection_name'])) {
                    $config['connection_name'] = !empty($name) ? $name : null;
                }

                $hermes->setConfig($config);

                return $hermes;
            }
        );
    }

    /**
     * Get the configuration file path.
     *
     * @param string $path
     * @return string
     */
    private function getConfigPath($path = '')
    {
        return $this->app->basePath() . '/config' . ($path ? '/' . $path : $path);
    }

    public function provides()
    {
        return [Hermes::class];
    }
}
