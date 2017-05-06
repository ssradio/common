<?php

namespace SSRadio\Common;

class Tracklist {

	protected $tracklist;

	public function __construct() {
	}

	public static function from_arp_json( $json ) {

		$instance = new Tracklist;

		$arp_data = json_decode( $json );

		if ( is_object( $arp_data ) && isset( $arp_data->results ) && ! empty( $arp_data->results ) ) {

			$results = $arp_data->results;

			foreach ( $results->result as $result ) {

				error_log( print_r( $result, true ) );

				// TODO: Process ARP results
				$track = [
					'start' => intval( $result->start ),
					'end' => intval( $result->end ),
				];
				$instance->tracklist[] = $track;
			}
		}

		return $instance;
	}

	public function __toString() {
		return print_r( $this->tracklist, true );
	}
}
