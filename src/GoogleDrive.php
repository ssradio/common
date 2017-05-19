<?php

namespace SSRadio\Common;

use \Google_Service_Drive;
use \Google_Client;

class GoogleDrive {

	/**
	 * Google Drive API Client.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Object	$client The client object.
	 */
	public $client;

	/**
	 * Google Drive API Service.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Object	$client The service object.
	 */
	public $service;

	protected $scopes;
	protected $folder_mime_type;
	protected $application_name;
	protected $client_secret_path;

	/**
	 *  __construct
	 *
	 *  This function will setup the helper
	 *
	 *  @return	n/a
	 */
	function __construct( $application_name, $client_secret_path ) {

		$this->application_name = $application_name;
		$this->client_secret_path = $client_secret_path;

		$this->scopes = implode( ' ', array( Google_Service_Drive::DRIVE ) );
		$this->folder_mime_type = 'application/vnd.google-apps.folder';

		// Get the API client and construct the service object.
		$this->client = $this->get_google_api_client();
		$this->service = new Google_Service_Drive( $this->client );
	}

	/**
	 * Returns an authorized API client.
	 *
	 * @return Google_Client the authorized client object
	 */
	private function get_google_api_client() {

		$client = new Google_Client();

		$client->setApplicationName( $this->application_name );
		$client->setScopes( $this->scopes );
		$client->setAuthConfig( $this->client_secret_path );
		$client->setAccessType( 'offline' );

		$client->useApplicationDefaultCredentials();

		return $client;
	}

	/**
	 * Gets a Google Drive folder by name.
	 *
	 * @since	1.0.0
	 * @param	string $name	The folder name.
	 * @param	string $parent	The parent folder object.
	 */
	public function get_folder_by_name( $name, $parent = null ) {

		if ( $parent ) {
			$query = sprintf( "name = '%s' AND mimeType = '%s' AND '%s' in parents", $name, $this->folder_mime_type, $parent->getId() );
		} else {
			$query = sprintf( "name = '%s' AND mimeType = '%s'", $name, $this->folder_mime_type );
		}

		$opt_params = array(
			'pageSize' => 10,
			'fields' => 'nextPageToken, files(id, name, size, properties)',
			'orderBy' => 'name,createdTime',
			'q' => $query,
		);
		$results = $this->service->files->listFiles( $opt_params );

		if ( count( $results->getFiles() ) == 1 ) {
			return $results->getFiles()[0];
		}
		return false;
	}

	/**
	 * Gets the files in a Google Drive folder.
	 *
	 * @since	1.0.0
	 * @param	string       $folder	The folder object.
	 * @param	string/Array $mimetypes	Optionaly only get files of these MIME types.
	 */
	public function get_files_in_folder( $folder, $mimetypes ) {

		$queries[] = sprintf( "'%s' in parents", $folder->getId() );

		if ( $mimetypes ) {
			if ( is_array( $mimetypes ) ) {
				$mimetype_queries = [];

				foreach ( $mimetypes as $mimetype ) {
					$mimetype_queries[] = sprintf( "mimeType = '%s'", $mimetype );
				}
				$mimetype_query = sprintf( '(%s)', join( ' or ', $mimetype_queries ) );
			} else {
				$mimetype_query = sprintf( "mimeType = '%s'", $mimetypes );
			}
			$queries[] = $mimetype_query;
		}

		$query = join( ' and ', $queries );

		$opt_params = array(
			'pageSize' => 1000,
			'fields' => 'nextPageToken, files(id, name, size, properties)',
			'orderBy' => 'name,createdTime',
			'q' => $query,
		);
		$results = $this->service->files->listFiles( $opt_params );

		if ( count( $results->getFiles() ) ) {
			return $results->getFiles();
		}
		return false;
	}

	/**
	 * Handle the audio uploads
	 *
	 * @since	1.0.0
	 * @param	string $name	The file name.
	 * @param	string $file	The path of the file.
	 * @param	string $folder	The folder name.
	 */
	public function upload_to_folder( $name, $file, $folder, $properties = [] ) {

		delete_transient( 'ssradio_audio_file_upload_progress_' . get_current_user_id() );

		$ods_folder_name = 'ODS';

		// Print the names and IDs for up to 10 files.
		$opt_params = array(
			'pageSize' => 10,
			'fields' => 'nextPageToken, files(id, name, size, properties)',
			'orderBy' => 'name,createdTime',
			'q' => sprintf( "name = '%s' AND mimeType = '%s'", $ods_folder_name, $this->folder_mime_type ),
		);
		$results = $this->service->files->listFiles( $opt_params );

		if ( count( $results->getFiles() ) == 0 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( 'No files found.' . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }
		} else if ( count( $results->getFiles() ) == 1 ) {
			$ods_folder = $results->getFiles()[0];
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'Found %s folder, ID is %s.', $ods_folder->getName(), $ods_folder->getId() ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }

			$opt_params = array(
				'pageSize' => 10,
				'fields' => 'nextPageToken, files(id, name, size, properties)',
				'orderBy' => 'name,createdTime',
				'q' => sprintf( "name = '%s' AND mimeType = '%s' AND '%s' in parents", $folder, $this->folder_mime_type, $ods_folder->getId() ),
			);
			$results = $this->service->files->listFiles( $opt_params );

			if ( count( $results->getFiles() ) == 0 ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( "Can't find %s folder, attempting to create...", $folder ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }

				$opt_params = array(
					'name' => $folder,
					'mimeType' => $this->folder_mime_type,
					'parents' => array( $ods_folder->getId() ),
				);
				$folder_metadata = new Google_Service_Drive_DriveFile( $opt_params );

				$show_folder = $this->service->files->create(
					$folder_metadata,
					array(
						'fields' => 'id',
					)
				);
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'Show folder %s created, ID is %s.', $folder, $show_folder->id ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }
			} else if ( count( $results->getFiles() ) == 1 ) {
				$show_folder = $results->getFiles()[0];
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'Found %s folder, ID is %s.', $show_folder->getName(), $show_folder->getId() ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }
			} else {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'Found %d folders in %s named %s!', count( $results->getFiles() ), $ods_folder->getName(), $folder ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }
			}

			if ( $show_folder ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'Attempting to upload file %s to %s folder...', $name, $folder ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }

				/* new */
				$opt_params = array(
					'name' => $name,
					'mimeType' => 'audio/mpeg',
					'parents' => array( $show_folder->getId() ),
					'properties' => $properties,
				);
				$drive_file = new Google_Service_Drive_DriveFile( $opt_params );
				$drive_file->title = $name;
				$chunk_size_bytes = 1 * 1024 * 1024;

				// Call the API with the media upload, defer so it doesn't immediately return.
				$this->client->setDefer( true );
				$request = $this->service->files->create( $drive_file );

				// Create a media file upload to represent our upload process.
				$media = new Google_Http_MediaFileUpload(
					$this->client,
					$request,
					'audio/mp3',
					null,
					true,
					$chunk_size_bytes
				);
				$filesize = filesize( $file );
				$media->setFileSize( $filesize );

				// Upload the various chunks. $status will be false until the process is
				// complete.
				$status = false;
				$handle = fopen( $file, 'rb' );

				while ( ! $status && ! feof( $handle ) ) {
					$chunk = fread( $handle, $chunk_size_bytes );
					$status = $media->nextChunk( $chunk );
					$percentage = number_format( ceil( ( $media->getProgress() / $filesize ) * 100 ), 0 );
					set_transient( 'ssradio_audio_file_upload_progress_' . get_current_user_id(), $percentage > 100 ? 100 : $percentage, MINUTE_IN_SECONDS * 10 );
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'Upload status: %d%%', $percentage ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }
				}

				// The final value of $status will be the data from the API for the object
				// that has been uploaded.
				$result = false;

				if ( false != $status ) {
					$result = $status;
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'File %s uploaded, ID is %s.', $result->name, $result->id ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }
				}

				fclose( $handle );

				// Reset to the client to execute requests immediately in the future.
				$this->client->setDefer( false );
			}
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) { error_log( sprintf( 'Found %d folders named %s!', count( $results->getFiles() ), $ods_folder_name ) . "\r\n", 3, WP_CONTENT_DIR . '/debug.log' ); }
		}
		return $results;
	}

	/**
	 * Delets a file from Google Drive.
	 *
	 * @since	1.0.0
	 * @param	string $file_id		The file ID.
	 */
	public function delete_file( $file_id ) {
		return $this->service->files->delete( $file_id );
	}
}
