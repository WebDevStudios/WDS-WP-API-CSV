<?php

class WDS_WP_API_CSV_Handler {

	/**
	 * Checks for '_csv' query param and downloads a csv of the json data
	 *
	 * @since  0.1.0
	 *
	 * @param  bool                      $served         Whether the request has already been served
	 * @param  WP_HTTP_ResponseInterface $result         Result to send to the client.
	 * @param  WP_REST_Request           $request        Request used to generate the response
	 * @param  WP_REST_Server            $wp_rest_server Server instance
	 *
	 * @return null
	 */
	public function check_for_csv_and_overload( $served, $result, $request, $wp_rest_server ) {
		if ( ! isset( $_GET['_csv'] ) ) {
			return $served;
		}

		if ( empty( $result->data ) ) {
			return $served;
		}

		$file = 'report.csv';
		header( "Content-Type: ;charset=utf-8" );
		header( "Content-Disposition: attachment;filename=\"$file\"" );
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		$csv = fopen('php://output', 'w');

		$done = false;


		foreach( $result->data as $post ) {

			// Do first csv column row
			if ( ! $done ) {
				$cols = $this->get_csv_column_headers( $post );
				fputcsv( $csv, $cols );
				$done = true;
			}


			$values = array();

			// Get some column values
			foreach ( $post as $column => $val ) {
				if ( ! in_array( $column, array(
					'_links',
					'guid',
					'content',
				) ) )	{
					$values[] = $this->assign_csv_value( $val );
				}
			}

			// Get associated links
			if ( isset( $post['_links'] ) ) {
				foreach ( $post['_links'] as $column => $col_value ) {

					if ( in_array( $column, array(
						'self',
						'collection',
						'author',
						'replies',
						'version-history',
						'http://v2.wp-api.org/attachment',
					) ) )	{
						continue;
					}

					foreach ( $col_value as $val ) {
						if ( isset( $val['href'] ) ) {
							$values[] = $val['href'];
						}
					}
				}
			}

			// and update the csv row
			fputcsv( $csv, $values );
		}

		// Download it
		fclose( $csv );
		exit();
	}

	/**
	 * Get column names from $post array
	 *
	 * @since  0.1.0
	 *
	 * @param  array  $post Array of post data
	 *
	 * @return array        Array of column names
	 */
	public function get_csv_column_headers( $post ) {
		$cols = array();
		foreach ( array_keys( (array) $post ) as $column ) {
			if ( ! in_array( $column, array(
				'_links',
				'guid',
				'content',
			) ) )	{
				$cols[] = $column;
			}
		}


		if ( isset( $post['_links'] ) ) {
			foreach ( $post['_links'] as $column => $col_value ) {

				if ( in_array( $column, array(
					'self',
					'collection',
					'author',
					'replies',
					'version-history',
					'http://v2.wp-api.org/attachment',
				) ) )	{
					continue;
				}

				foreach ( $col_value as $val ) {
					if ( isset( $val['href'] ) ) {
						$cols[] = isset( $val['taxonomy'] ) ? $val['taxonomy'] : $val['href'];
					}
				}
			}
		}

		error_log( '$cols: '. print_r( $cols, true ) );

		return $cols;
	}

	/**
	 * Assign a csv cell value. Needs to be scalar
	 *
	 * @since  0.1.0
	 *
	 * @param  mixed  $value Value given by API
	 *
	 * @return mixed         A scalar value
	 */
	public function assign_csv_value( $value ) {
		if ( isset( $value['rendered'] ) ) {
			$value = $value['rendered'];
		} elseif ( is_scalar( $value ) ) {
			$value = $value;
		} else {
			$value = 'needs-parsing';
		}

		return $value;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.1.0
	 * @param string $field
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
				return $this->$field;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}
}
