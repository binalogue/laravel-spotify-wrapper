<?php

namespace Binalogue\SpotifyWrapper;

use Binalogue\SpotifyWrapper\Concerns\HasAuthWrapper;
use Binalogue\SpotifyWrapper\Concerns\HasSpotifyWebApiWrapper;
use Illuminate\Support\Collection;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyWrapper
{
    use HasAuthWrapper;
    use HasSpotifyWebApiWrapper;

    /**
     * The Spotify options.
     *
     * @var array
     */
    protected $options;

    /**
     * The SpotifySession instance.
     *
     * @var Session
     */
    public $session;

    /**
     * The SpotifyWebAPI instance.
     *
     * @var SpotifyWebAPI
     */
    public $api;

    /**
     * Create a new Spotify instance.
     *
     * @param Session $session
     * @param SpotifyWebAPI $api
     * @param  array  $options
     * @return void
     */
    public function __construct(Session $session, SpotifyWebAPI $api, $options = [])
    {
        $this->session = $session;
        $this->api = $api;
        $this->options = $options;
    }
}
