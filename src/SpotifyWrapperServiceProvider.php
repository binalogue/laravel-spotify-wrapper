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
            $protocol = (
                !empty(request()->server('HTTPS'))
                    && request()->server('HTTPS') !== 'off'
                    || request()->server('SERVER_PORT') == 443
            )
                ? 'https://'
                : 'http://';
            $domainName = request()->server('HTTP_HOST');
            $callback = array_key_exists('callback', $parameters)
                ? $protocol . $domainName . $parameters['callback']
                : '';

            $session = new Session(
                config('services.spotify.client_id'),
                config('services.spotify.client_secret'),
                $callback
            );

            return new SpotifyWrapper(
                $session,
                new SpotifyWebAPI(),
                $parameters['options'] ?? []
            );
        });
    }
}
