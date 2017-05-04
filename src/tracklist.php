<?php

namespace SSRadio\Common;

class Tracklist {

	public function __construct() {
	}

	public static function from_arp_json( $json ) {

		$tracklist = new Tracklist;

		$arp_data = json_decode( $json );

		if ( is_object( $arp_data ) && isset( $arp_data->results ) && ! empty( $arp_data->results ) ) {

			foreach ( $arp_data->results as $result ) {
				// TODO: Process ARP results
			}
		}

		return $tracklist;
	}
}
