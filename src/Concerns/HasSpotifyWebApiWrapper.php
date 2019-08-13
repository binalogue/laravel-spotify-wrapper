<?php

namespace Binalogue\SpotifyWrapper\Concerns;

use Binalogue\SpotifyWrapper\Models\SpotifyTrack;
use Illuminate\Support\Collection;

trait HasSpotifyWebApiWrapper
{
    // Danceability values typical range between 0.20 and 0.80.
    protected $DANCEABILITY_MIN = 0.20;
    protected $DANCEABILITY_MAX = 0.80;

    // Energy values typical range between 0.10 and 0.90.
    protected $ENERGY_MIN = 0.10;
    protected $ENERGY_MAX = 0.90;

    // Loudness values typical range between -20 and 0 db.
    protected $LOUDNESS_MIN = -20;
    protected $LOUDNESS_MAX = 0;

    // Tempo values typical range between 70 and 200.
    protected $TEMPO_MIN = 70;
    protected $TEMPO_MAX = 200;

    /*
    |--------------------------------------------------------------------------
    | Protected Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate the happiness of a track.
     *
     * happiness = avg(loudness + tempo + energy + danceability)
     *
     * @param  \Illuminate\Support\Collection  $trackAudioFeatures
     * @return int|float
     */
    protected function calcHappiness($trackAudioFeatures)
    {
        return collect([
            $this->normalize(
                $trackAudioFeatures->loudness,
                $this->LOUDNESS_MIN,
                $this->LOUDNESS_MAX
            ),
            $this->normalize(
                $trackAudioFeatures->tempo,
                $this->TEMPO_MIN,
                $this->TEMPO_MAX
            ),
            $this->normalize(
                $trackAudioFeatures->energy,
                $this->ENERGY_MIN,
                $this->ENERGY_MAX
            ),
            $this->normalize(
                $trackAudioFeatures->danceability,
                $this->DANCEABILITY_MIN,
                $this->DANCEABILITY_MAX
            )
        ])->avg();
    }

    /**
     * Calculate the mood of a track.
     *
     * https://medium.com/@mohithsubbarao/moodtape-using-spotify-api-to-create-mood-generated-playlists-6e1244c70892
     *
     * @param  \Illuminate\Support\Collection  $trackAudioFeatures
     * @return string
     */
    protected function calcMood($trackAudioFeatures): string
    {
        $moodMin = 0.90;
        if (
            $trackAudioFeatures->valence >= ($moodMin - 0.15)
            && $trackAudioFeatures->danceability >= ($moodMin / 1.75)
            && $trackAudioFeatures->energy >= ($moodMin / 1.5)
        ) {
            return 'danceable';
        }

        $moodMin = 0.50;
        $moodMax = 0.90;
        if (
            $trackAudioFeatures->valence >= ($moodMin - 0.075)
            && $trackAudioFeatures->valence <= ($moodMax + 0.075)
            && $trackAudioFeatures->danceability >= ($moodMin / 2.5)
            && $trackAudioFeatures->energy >= ($moodMin / 2)
        ) {
            return 'happy';
        }

        $moodMin = 0.20;
        $moodMax = 0.50;
        if (
            $trackAudioFeatures->valence >= ($moodMin - 0.05)
            && $trackAudioFeatures->valence <= ($moodMax + 0.05)
            && $trackAudioFeatures->danceability >= ($moodMin * 1.75)
            && $trackAudioFeatures->energy >= ($moodMin * 1.75)
        ) {
            return 'chill';
        }

        return 'sad';
    }

    /**
     * Normalize number.
     *
     * z = (x - min(x)) / (max(x) - min(x))
     *
     * @param  int|float  $value
     * @param  int|float  $min
     * @param  int|float  $max
     * @return int|float
     */
    protected function normalize($value, $min, $max)
    {
        if ($value < $min) {
            return 0;
        } elseif ($value > $max) {
            return 1;
        }

        return ($value - $min) / ($max - $min);
    }

    /**
     * Sanitize the playlist tracks limit option.
     *
     * @param  int  $limit
     * @return int
     */
    protected function sanitizePlaylistSize(int $limit = 20): int
    {
        if (!is_int($limit)) {
            return 20;
        }

        if ($limit < 20) {
            return 20;
        } elseif ((20 <= $limit) && ($limit <= 50)) {
            return $limit;
        } else {
            return 50;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Spotify API Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Add tracks to a playlist.
     * https://developer.spotify.com/documentation/web-api/reference/playlists/add-tracks-to-playlist/
     *
     * @param string $playlistId ID of the playlist to add tracks to.
     * @param string|array $tracks ID(s) or Spotify URI(s) of the track(s) to add.
     * @param array $options Optional. Options for the new tracks.
     * - int position Optional. Zero-based track position in playlist. Tracks will be appened if omitted or false.
     *
     * @return bool Whether the tracks was successfully added.
     */
    public function addPlaylistTracks($playlistId, $tracks, $options = [])
    {
        return $this->api->addPlaylistTracks($playlistId, $tracks, $options);
    }

    /**
     * Create a new playlist.
     * https://developer.spotify.com/documentation/web-api/reference/playlists/create-playlist/
     *
     * @param array $options Options for the new playlist.
     * - string name Required. Name of the playlist.
     * - bool public Optional. Whether the playlist should be public or not.
     *
     * @return object The new playlist.
     */
    public function createPlaylist(array $options = []): object
    {
        return $this->api->createPlaylist($options);
    }

    /**
     * Get track audio features.
     * https://developer.spotify.com/documentation/web-api/reference/tracks/get-several-audio-features/
     *
     * @param array $trackIds IDs or Spotify URIs of the tracks.
     *
     * @return \Illuminate\Support\Collection The tracks' audio features.
     */
    public function getAudioFeatures(array $trackIds): Collection
    {
        return collect(
            $this->api->getAudioFeatures($trackIds)->audio_features
        );
    }

    /**
     * Get the current user's top artists.
     * https://developer.spotify.com/documentation/web-api/reference/personalization/get-users-top-artists-and-tracks/
     *
     * @param array $options. Optional. Options for the results.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     * - mixed time_range Optional. Over what time frame the data is calculated. See Spotify API docs for more info.
     *
     * @return \Illuminate\Support\Collection A collection of the requested top artists.
     */
    public function getMyTopArtists(array $options = []): Collection
    {
        return collect(
            $this->api->getMyTop('artists', $options)->items
        );
    }

    /**
     * Get the current user's top tracks.
     * https://developer.spotify.com/documentation/web-api/reference/personalization/get-users-top-artists-and-tracks/
     *
     * @param array $options. Optional. Options for the results.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     * - mixed time_range Optional. Over what time frame the data is calculated. See Spotify API docs for more info.
     *
     * @return \Illuminate\Support\Collection A collection of the requested top tracks.
     */
    public function getMyTopTracks(array $options = []): Collection
    {
        return collect(
            $this->api->getMyTop('tracks', $options)->items
        );
    }

    /**
     * Get the current user's top tracks IDs.
     *
     * @param array $options. Optional. Options for the results.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     * - mixed time_range Optional. Over what time frame the data is calculated. See Spotify API docs for more info.
     *
     * @return array An array of the requested top tracks IDs.
     */
    public function getMyTopTracksIds(array $options = []): array
    {
        return $this
            ->getMyTopTracks($options)
            ->map(function ($track) {
                return $track->id;
            })
            ->toArray();
    }

    /**
     * Get a specific playlist.
     * https://developer.spotify.com/documentation/web-api/reference/playlists/get-playlist/
     *
     * @param string $playlistId ID or Spotify URI of the playlist.
     * @param array $options Optional. Options for the playlist.
     * - string|array fields Optional. A list of fields to return. See Spotify docs for more info.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return object The user's playlist.
     */
    public function getPlaylist(string $playlistId, array $options = []): object
    {
        return $this->api->getPlaylist($playlistId, $options);
    }

    /**
     * Get the tracks in a playlist.
     * https://developer.spotify.com/documentation/web-api/reference/playlists/get-playlists-tracks/
     *
     * @param string $playlistId ID or Spotify URI of the playlist.
     * @param array $options Optional. Options for the tracks.
     * - string|array fields Optional. A list of fields to return. See Spotify docs for more info.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return \Illuminate\Support\Collection The tracks in the playlist.
     */
    public function getPlaylistTracks(string $playlistId, array $options = []): Collection
    {
        return collect(
            $this->api->getPlaylistTracks($playlistId, $options)->items
        )
            ->map(function ($item) {
                return $item->track;
            });
    }

    /**
     * Get recommendations based on artists, tracks, or genres.
     * https://developer.spotify.com/documentation/web-api/reference/browse/get-recommendations/
     *
     * @param array $options Optional. Options for the recommendations.
     * - int limit Optional. Limit the number of recommendations.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     * - mixed max_* Optional. Max value for one of the tunable track attributes.
     * - mixed min_* Optional. Min value for one of the tunable track attributes.
     * - array seed_artists Artist IDs to seed by.
     * - array seed_genres Genres to seed by. Call SpotifyWebAPI::getGenreSeeds() for a complete list.
     * - array seed_tracks Track IDs to seed by.
     * - mixed target_* Optional. Target value for one of the tunable track attributes.
     *
     * @return \Illuminate\Support\Collection The requested recommendations.
     */
    public function getRecommendations(array $options = []): Collection
    {
        return collect(
            $this->api->getRecommendations($options)->tracks
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Music Analyzer
    |--------------------------------------------------------------------------
    */

    /**
     * Get the happiness of a unique track.
     *
     * @param  string  $trackId
     * @return int|float
     */
    public function getHappiness(string $trackId)
    {
        return $this->calcHappiness(
            $this->getAudioFeatures([$trackId])->first()
        );
    }

    /**
     * Get my recommended tracks, based on my top tracks.
     *
     * @param  \Illuminate\Support\Collection  $referenceTracks
     * @param  array  $options
     * @param  array  $recommendationsOptions
     * @return \Illuminate\Support\Collection
     */
    public function getMyRecommendedTracks(
        Collection $referenceTracks,
        array $options,
        array $recommendationsOptions
    ): Collection {
        $size = $this->sanitizePlaylistSize($options['size']);

        $myTracks = collect([]);

        do {
            $referenceTracks->shuffle()->each(function ($track) use (
                $myTracks,
                $recommendationsOptions,
                $size
            ) {
                if ($myTracks->count() >= $size) {
                    return false;
                }

                $this->getRecommendations(array_merge([
                    'limit' => 5,
                    'seed_tracks' => [$track->id],
                ], $recommendationsOptions))
                    ->reject(function ($track) use ($myTracks) {
                        return $myTracks->contains('id', $track->id);
                    })
                    ->whenNotEmpty(function ($recommendedTracks) use ($myTracks) {
                        $myTracks->push($recommendedTracks->random());
                    });
            });
        } while ($myTracks->count() < $size);

        return $myTracks;
    }

    /**
     * Get my top tracks filtered by the given parameters.
     *
     * @param  array  $filters
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function getMyRecommendedTracksFilteredBy(array $filters = [], array $options = []): Collection
    {
        Collection::macro('applyFilters', function ($filteredTracks, $filters, $size = 20) {
            return $this->reject(function ($track) use ($filteredTracks) {
                return $filteredTracks->contains('id', $track->id);
            })
                ->when($filters['popularity'], function ($collection) use ($filters) {
                    return $collection->filter(function ($track) use ($filters) {
                        return (new SpotifyTrack($track))->checkPopularity($filters['popularity']);
                    });
                })
                ->take($size - $filteredTracks->count());
        });

        $size = $this->sanitizePlaylistSize($options['size']);

        $filteredTracks = collect([]);

        $loopOffsetA = 0;
        $loopAttempsA = 0;
        $maxAttempsA = 5;
        do {
            $filteredTracks = $filteredTracks->merge(
                $this->getMyTopTracks([
                    'limit' => $size, // Maximum: 50
                    'offset' => $loopOffsetA,
                ])->applyFilters($filteredTracks, $filters, $size)
            );

            $loopOffsetA += $size;
            $loopAttempsA += 1;
        } while (
            $loopAttempsA < $maxAttempsA
            && $filteredTracks->count() < $size
        );

        $loopOffsetB = 0;
        $loopAttempsB = 0;
        $maxAttempsB = 5;
        do {
            $referenceTracks = $this->getMyTopTracks([
                'limit' => $size, // Maximum: 50
                'offset' => $loopOffsetB,
            ]);

            $filteredTracks = $filteredTracks->merge(
                $this->getMyRecommendedTracks(
                    $referenceTracks,
                    [
                        'size' => $size - $filteredTracks->count(),
                    ],
                    [
                        'target_popularity' => $filters['popularity'],
                    ]
                )->applyFilters($filteredTracks, $filters, $size)
            );

            $loopOffsetB += $size;
            $loopAttempsB += 1;
        } while (
            $loopAttempsB < $maxAttempsB
            && $filteredTracks->count() < $size
        );

        return $filteredTracks;
    }

    /**
     * Get the happiness of an array of track IDs.
     *
     * @param  array  $trackIds
     * @return int|float
     */
    public function getSeveralHappiness(array $trackIds)
    {
        return $this->getAudioFeatures($trackIds)
            ->map(function ($trackAudioFeatures) {
                return $this->calcHappiness($trackAudioFeatures);
            })
            ->avg();
    }

    /**
     * Get the mood of an array of track IDs.
     *
     * @param  array  $trackIds
     * @return string
     */
    public function getSeveralMood(array $trackIds): string
    {
        return $this->getAudioFeatures($trackIds)
            ->map(function ($trackAudioFeatures) {
                $trackAudioFeatures->mood = $this->calcMood($trackAudioFeatures);
                return $trackAudioFeatures;
            })
            ->groupBy('mood')
            ->sortByDesc(function ($group) {
                return count($group);
            })
            ->keys()
            ->first();
    }

    /**
     * Get the top genre of a collection of artists.
     *
     * @param  \Illuminate\Support\Collection  $artists
     * @return string
     */
    public function getTopGenre(Collection $artists): string
    {
        return $artists
            ->groupBy('genres')
            ->sortByDesc(function ($group) {
                return count($group);
            })
            ->keys()
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */


}
