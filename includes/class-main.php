<?php
/**
 * Main plugin class file.
 *
 * @package snordians-h5p-themer
 */

namespace Snordian\H5PThemer;

// as suggested by the WordPress community.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Main plugin class.
 */
class Main {

	/**
	 * Constructor.
	 */
	public function __construct() {
		new Options();
		add_action( 'add_option', array( self::class, 'handle_option_added' ), 10, 2 );
		add_action( 'update_option', array( self::class, 'handle_option_updated' ), 10, 3 );
	}

	/**
	 * Handle first-time creation of options.
	 *
	 * @param string $option_name The name of the option that was added.
	 * @param mixed  $new_value The new value of the option.
	 */
	public static function handle_option_added( $option_name, $new_value ) {
			self::handle_option_updated( $option_name, null, $new_value );
	}

	/**
	 * Handle changes to the endpoint URL base option that other plugins might use.
	 *
	 * @param string $option_name The name of the option that was updated.
	 * @param mixed  $old_value The old value of the option.
	 * @param mixed  $new_value The new value of the option.
	 */
	public static function handle_option_updated( $option_name, $old_value, $new_value ) {
		if ( Options::get_slug() !== $option_name ) {
			return;
		}

		if ( is_array( $new_value ) && isset( $new_value['picker_values'] ) ) {
			$json_string = $new_value['picker_values'];
			$decoded     = json_decode( $json_string, true );

			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
					return;
			}

			$density = isset( $decoded['data']['density'] ) ? (string) $decoded['data']['density'] : '';
			$colors  = isset( $decoded['data']['colors'] ) && is_array( $decoded['data']['colors'] )
					? $decoded['data']['colors']
					: '';

			$fields = array();

			if ( '' !== $density ) {
					$fields[] = '  density: ' . wp_json_encode( $density );
			}

			if ( ! empty( $colors ) ) {
					$fields[] = '  colors: ' . wp_json_encode( $colors, JSON_PRETTY_PRINT );
			}

			$config_content = 'window.H5P_THEMER = {' . "\n"
					. implode( ",\n", $fields ) . "\n"
					. '};' . "\n";

			$config_path = plugin_dir_path( __DIR__ ) . 'h5p-themer-config.js';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();

			global $wp_filesystem;

			if ( $wp_filesystem ) {
				$wp_filesystem->put_contents( $config_path, $config_content, FS_CHMOD_FILE );
			}
		}
	}
}
