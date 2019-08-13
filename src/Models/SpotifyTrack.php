<?php

namespace Binalogue\SpotifyWrapper\Models;

class SpotifyTrack
{
    /**
     * Yhe Spotify track object.
     *
     * @var object
     */
    protected $track;

    /**
     * Create a new SpotifyTrack instance.
     *
     * @param  object  $track
     * @return void
     */
    public function __construct(object $track)
    {
        $this->track = $track;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the track popularity matches a target popularity.
     *
     * @param  int  $targetPopularity
     * @return bool
     */
    public function checkPopularity(int $targetPopularity): bool
    {
        if (70 < $targetPopularity) {
            if (70 <= $this->track->popularity) {
                return true;
            }
        } elseif (30 < $targetPopularity) {
            if ((30 <= $this->track->popularity) && ($this->track->popularity <= 70)) {
                return true;
            }
        } else {
            if ($this->track->popularity <= 30) {
                return true;
            }
        }

        return false;
    }
}
