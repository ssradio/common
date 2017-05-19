<?php

namespace SSRadio\Common;

class Tracklist {

	protected $tracklist;
	protected $arp_json;

	public function __construct() {
	}

	public static function from_arp_json( $json ) {

		$instance = new Tracklist;

		$instance->arp_json = $json;

		$arp_data = json_decode( $instance->arp_json );

		if ( is_object( $arp_data ) && isset( $arp_data->results ) && ! empty( $arp_data->results ) ) {

			$results = $arp_data->results;

			foreach ( $results->result as $result ) {

				// Process ARP results
				$track = [
					'start' => intval( $result->start ),
					'end' => intval( $result->end ),
				];

				$trackclients = $result->trackclients;
				$trackclient = $trackclients->trackclient;

				if ( ! empty( $trackclient ) ) {
					$singles = array_values( array_filter( $trackclient, function( $track ) {
						return 'single' === $track->class;
					} ) );

					if ( empty( $singles ) ) {
						// No singles found, so use first track.
						$chosen = $trackclient[0];
					} else {
						$chosen = $singles[0];
					}
					$track['title_id'] = $chosen->title_id;
					$track['artist'] = join( ', ', $chosen->bundle_mirror_artists->artist );

					$pos = strrpos( $track['artist'], ', ' );

					if ( false !== $pos ) {
						$track['artist'] = substr_replace( $track['artist'], ' & ', $pos, strlen( ', ' ) );
					}
					$track['title'] = $chosen->track_title;
					$track['mix'] = $chosen->track_mix_title ? ucwords( $chosen->track_mix_title ) : 'Original Mix';
					$track['label'] = $chosen->label;
					$track['genre'] = $chosen->track_genre;
				}
				$instance->tracklist[] = $track;
			}

		}  // End if().

		return $instance;
	}

	public function __toString() {
		$tracklist = [];

		foreach( $this->tracklist as $track ) {
			if ( isset( $track['artist'] ) ) {
				$tracklist[] = sprintf( '%s - %s (%s) [%s]', $track['artist'], $track['title'], $track['mix'], $track['label'] );
			}
		}
		return join( "\r\n", $tracklist );
	}

	public function toJson() {
		return json_encode( $this->tracklist );
	}
}
