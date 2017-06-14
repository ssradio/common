<?php

namespace SSRadio\Common;

class JunoARP {

	/**
	 * Juno ARP API entry point URL.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Object	$entry_point_url The request URL.
	 */
	private $entry_point_url;

	/**
	 * Juno ARP API key.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Object	$api_key The API key.
	 */
	private $api_key;

	/**
	 *  __construct
	 *
	 *  This function will setup the helper
	 *
	 *  @return	n/a
	 */
	public function __construct( $api_key, $entry_point_url = 'https://www.junodownload.com/api/1.2/arp' ) {

		$this->api_key = $api_key;
		$this->entry_point_url = $entry_point_url;
	}

	/**
	 * Generates an API request URL.
	 *
	 * @since	1.0.0
	 * @param	string $method			The API method.
	 */
	private function generate_request_url( $method ) {

		return sprintf( '%s/%s', $this->entry_point_url, $method );
	}

	/**
	 * Posts an API request.
	 *
	 * @since	1.0.0
	 * @param	string $method			The API method.
	 * @param	Array  $request_args	The API request data.
	 */
	private function make_request( $method, $request_args ) {

		// Generates the request URL
		// example:  'https://www.junodownload.com/api/1.2/arp/setFeedback';
		$request_url = $this->generate_request_url( $method );

		// Post the request
		$result = wp_remote_post(
			$request_url,
			$request_args
		);

		return $result;
	}

	/**
	 * Sets an endpoint URL that success or error events will be sent to for each setSource request.
	 * This endpoint will be used for all susequent setSource requests until changed.
	 *
	 * @since	1.0.0
	 * @param	string $feedback_url The endpoint URL.
	 */
	public function set_feedback_url( $feedback_url ) {

		// Prepare the arguments for the setFeedback request
		$request_args = array(
			'body' => array(
				'key' => $this->api_key,
				'url' => $feedback_url,
			),
		);

		return $this->make_request( 'setFeedback', $request_args );
	}

	/**
	 * Creates a new audio source to be analysed.
	 *
	 * @since	1.0.0
	 * @param	string $source		The URL of an MP3 audio file (max. 1024 characters).
	 * @param	string $name		The name of the source (max. 255 characters).
	 * @param	string $author		The author of the source (max. 50 characters).
	 * @param	string $ref			Optional reference to store with the source (max. 255 characters).
	 * @param	string $date		Optional ISO 8601 date that the store was created, i.e. 2012-04-13T14:55:24Z.
	 * @param	string $description	Optional description for the store (max. 4000 characters).
	 */
	public function set_source( $source, $name = '', $author = '', $ref = '', $date = '', $description = '' ) {

		// Prepare the arguments for the setSource request
		$request_args = array(
			'body' => array(
				'key' => $this->api_key,
				'name' => $name,
				'source' => $source,
				'author' => $author,
			),
		);

		if ( ! empty( $ref ) ) {
			$request_args['body']['ref'] = $ref;
		}

		if ( ! empty( $date ) ) {
			$request_args['body']['date'] = $date;
		}

		if ( ! empty( $description ) ) {
			$request_args['body']['description'] = $description;
		}

		return $this->make_request( 'setSource', $request_args );
	}

	/**
	 * Returns information about a source.
	 *
	 * @since	1.0.0
	 * @param	string $guid The GUID of the source.
	 */
	public function get_source( $guid ) {

		// Prepare the arguments for the getSource request
		$request_args = array(
			'body' => array(
				'key' => $this->api_key,
				'guid' => $guid,
			),
		);

		// Post the request
		return $this->make_request( 'getSource',  $request_args );
	}

	/**
	 * Returns the results of the source processing.
	 *
	 * @since	1.0.0
	 * @param	string $guid The GUID of the source.
	 * @param	bool   $json If true, return the list of tracks in JSON format.
	 */
	public function get_results( $guid, $json = true ) {

		// Prepare the arguments for the getResults request
		$request_args = array(
			'body' => array(
				'key' => $this->api_key,
				'guid' => $guid,
			),
		);

		$endpoint = $json ? 'getResultsUser' : 'getResults';

		// Post the request
		return $this->make_request( $endpoint,  $request_args );
	}

	/**
	 * Returns a list of all the ARP statuses currently supported by Juno.
	 * These can be found in the status_id node of the source object, to allow you to check the status of a source.
	 *
	 * @since	1.0.0
	 */
	public function get_arp_statuses() {

		// Prepare the arguments for the getARPStatuses request
		$request_args = array(
			'body' => array(
				'key' => $this->api_key,
			),
		);

		// Post the request
		return $this->make_request( 'getARPStatuses',  $request_args );
	}
}
