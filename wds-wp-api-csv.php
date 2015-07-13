<?php
/**
* Plugin Name: WDS WP-API CSV
* Plugin URI:  http://webdevstudios.com
* Description: Output WP-API json data to CSV
* Version:     0.1.0
* Author:      WebDevStudios
* Author URI:  http://webdevstudios.com
* Donate link: http://webdevstudios.com
* License:     GPLv2
* Text Domain: wds-wp-api-csv
* Domain Path: /languages
*/

/**
 * Copyright (c) 2015 WebDevStudios (email : contact@webdevstudios.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using generator-plugin-wp
 */

/**
 * Main initiation class
 *
 * @since  0.1.0
 * @var  string $version  Plugin version
 * @var  string $basename Plugin basename
 * @var  string $url      Plugin URL
 * @var  string $path     Plugin Path
 */
class WDS_WP_API_CSV {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since  0.1.0
	 */
	const VERSION = '0.1.0';

	/**
	 * URL of plugin directory
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $path = '';

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $basename = '';

	/**
	 * Singleton instance of plugin
	 *
	 * @var WDS_WP_API_CSV
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  0.1.0
	 * @return WDS_WP_API_CSV A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 *
	 * @since  0.1.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );

		$this->plugin_classes();
		$this->hooks();
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since 0.1.0
	 * @return  null
	 */
	function plugin_classes() {
		// Attach other plugin classes to the base plugin class.
		// $this->admin = new WDSWPAPICSV_Admin( $this );
	}

	/**
	 * Add hooks and filters
	 *
	 * @since 0.1.0
	 * @return null
	 */
	public function hooks() {
		register_activation_hook( __FILE__, array( $this, '_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, '_deactivate' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Activate the plugin
	 *
	 * @since  0.1.0
	 * @return null
	 */
	function _activate() {
		// Make sure any rewrite functionality has been loaded
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin
	 * Uninstall routines should be in uninstall.php
	 *
	 * @since  0.1.0
	 * @return null
	 */
	function _deactivate() {}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 * @return null
	 */
	public function init() {
		if ( $this->check_requirements() ) {
			add_filter( 'rest_pre_serve_request', array( $this, 'check_for_csv_and_overload' ), 10, 4 );
		}
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @since  0.1.0
	 * @return boolean
	 */
	public static function meets_requirements() {
		$plugin = 'wp-api/plugin.php';

		$plugins_activated = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		$plugins_network_activated = apply_filters( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins' ) );

		// Normal plugin activation looks different than network-wide activation
		$plugin_activated = in_array( $plugin, $plugins_activated );
		$plugin_network_activated = array_key_exists( $plugin, $plugins_network_activated );

		return ! $plugin_activated && ! $plugin_network_activated;
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.1.0
	 * @return boolean result of meets_requirements
	 */
	public function check_requirements() {
		if ( ! $this->meets_requirements() ) {

			// Add a dashboard notice
			add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

			// Deactivate our plugin
			deactivate_plugins( $this->basename );

			return false;
		}

		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met
	 *
	 * @since  <%= version %>
	 * @return null
	 */
	public function requirements_not_met_notice() {
		// Output our error
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'WDS WP-API CSV is missing requirements (the WP-API plugin) and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'wds-wp-api-csv' ), admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}

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

/**
 * Grab the WDS_WP_API_CSV object and return it.
 * Wrapper for WDS_WP_API_CSV::get_instance()
 *
 * @since  0.1.0
 * @return WDS_WP_API_CSV  Singleton instance of plugin class.
 */
function wds_wp_api_csv() {
	return WDS_WP_API_CSV::get_instance();
}

// Kick it off
wds_wp_api_csv();
