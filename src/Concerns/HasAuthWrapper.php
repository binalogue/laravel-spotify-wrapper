<?php

namespace Binalogue\SpotifyWrapper\Concerns;

trait HasAuthWrapper
{
    /**
     * Redirect to the Spotify authorize URL.
     *
     * @return void
     */
    protected function redirectToSpotifyAuthorizeUrl()
    {
        header("Location: {$this->session->getAuthorizeUrl($this->options)}");
        die();
    }

    /**
     * Authentication process.
     *
     * If refresh token provided, we refresh the access token and then we set it
     * to the API.
     *
     * If no, we first have to request an access token.
     *
     * @param  string|null  $refreshToken
     * @return Binalogue\SpotifyWrapper\SpotifyWrapper|void
     */
    public function auth($refreshToken = null)
    {
        if (is_null($refreshToken)) {
            $this->requestAccessToken();
        } else {
            $this->refreshAccessToken($refreshToken);
        }

        return $this->setAccessTokenFromSession();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Refresh the session access token.
     *
     * @param  string  $refreshToken
     * @return \Binalogue\SpotifyWrapper\SpotifyWrapper
     */
    public function refreshAccessToken(string $refreshToken)
    {
        $this->session->refreshAccessToken($refreshToken);
        return $this;
    }

    /**
     * Request a session access token.
     *
     * @return Binalogue\SpotifyWrapper\SpotifyWrapper|void
     */
    public function requestAccessToken()
    {
        try {
            $this->session->requestAccessToken($_GET['code']);
            return $this;
        } catch (\Exception $ex) {
            $this->redirectToSpotifyAuthorizeUrl();
        }
    }

    /**
     * Set the API access token from the session.
     *
     * @return Binalogue\SpotifyWrapper\SpotifyWrapper
     */
    public function setAccessTokenFromSession()
    {
        $this->setAccessToken($this->getAccessToken());
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters and Setters
    |--------------------------------------------------------------------------
    */

    /**
     * Get the session access token.
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->session->getAccessToken();
    }

    /**
     * Get the session refresh token.
     *
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->session->getRefreshToken();
    }

    /**
     * Set the API access token.
     *
     * @param  string  $accessToken
     * @return void
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->api->setAccessToken($accessToken);
    }
}
