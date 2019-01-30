<?php

namespace Eduzz\Hermes;

use Illuminate\Support\ServiceProvider;

use Eduzz\Hermes\Hermes;

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

                $hermes->setConfig(config('hermes.connection'));

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
    private function getConfigPath($path = '') {
        return $this->app->basePath() . '/config' . ($path ? '/' . $path : $path);
    }

    public function provides()
    {
        return [Hermes::class];
    }
}
