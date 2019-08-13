<?php

namespace Binalogue\SpotifyWrapper;

use Binalogue\SpotifyWrapper\SpotifyWrapper;
use Illuminate\Support\ServiceProvider;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyWrapperServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton('SpotifyWrapper', function ($app, $parameters = [])
        {
            $session = new Session(
                config('services.spotify.client_id'),
                config('services.spotify.client_secret'),
                array_key_exists('callback', $parameters) ? config('app.url') . $parameters['callback'] : ''
            );

            return new SpotifyWrapper($session, new SpotifyWebAPI(), $parameters['options'] ?? []);
        });
    }
}
