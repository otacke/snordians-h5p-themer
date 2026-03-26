<?php
/**
 * Plugin Name: SNORDIAN's H5P Themer
 * Plugin URI: https://github.com/otacke/snordians-h5p-themer
 * Text Domain: snordians-h5p-themer
 * Description: Use H5P's theming capabilities that the H5P plugin denies to offer
 * Version: 0.0.4
 * Requires at least: 6.5
 * Author: Oliver Tacke (SNORDIAN)
 * Author URI: https://snordian.de
 * License: MIT
 *
 * @package snordians-h5p-themer
 */

namespace Snordian\H5PThemer;

// as suggested by the WordPress community.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! defined( 'SNORDIANSH5PTHEMER_VERSION' ) ) {
	define( 'SNORDIANSH5PTHEMER_VERSION', '0.0.4' );
}

require_once join( DIRECTORY_SEPARATOR, array( __DIR__, 'includes', 'class-capabilities.php' ) );
require_once join( DIRECTORY_SEPARATOR, array( __DIR__, 'includes', 'class-main.php' ) );
require_once join( DIRECTORY_SEPARATOR, array( __DIR__, 'includes', 'class-options.php' ) );

/**
 * Main plugin class.
 *
 * @return object Main.
 */
function init() {
	// Does NOT work if omitted locally - is this a chicken and egg problem? Needs to be on wordpress.org?
    // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
	load_plugin_textdomain(
		'snordians-h5p-themer',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	if ( ! is_admin() ) {
		return;
	}

	return new Main();
}

/**
 * Handle plugin activation.
 */
function on_activation() {
	if ( ! dependencies_fulfilled() ) {
		set_transient( 'snordiansh5pthemer_h5p_too_old', true, 30 );
	}

	Capabilities::add_capabilities();
}

/**
 * Check whether all dependencies are installed.
 *
 * @return bool True if dependencies are fulfilled, else false.
 */
function dependencies_fulfilled() {
	$h5p_plugin_version = get_option( 'h5p_version' );

	return ! empty( $h5p_plugin_version ) && version_compare( $h5p_plugin_version, '1.17.4', '>=' );
}

/**
 * Display admin messages.
 */
function display_admin_messages() {
	if ( get_transient( 'snordiansh5pthemer_h5p_too_old' ) ) {
		delete_transient( 'snordiansh5pthemer_h5p_too_old' );
		// Does not get translated. Wonder why.
		echo '<div class="error">' .
			'<p>' .
				esc_html__(
					'Note: This plugin requires the H5P plugin in version 1.17.4 at least.',
					'snordians-h5p-themer'
				) .
			'</p>' .
		'</div>';
	}
}

/**
 * Handle plugin uninstallation.
 */
function on_uninstall() {
	Options::delete_options();
	Capabilities::remove_capabilities();
}

/**
 * Pass scripts to H5P.
 *
 * @param Array $scripts List of JavaScripts that H5P will load.
 */
function h5p_themer_alter_scripts( &$scripts ) {
	$scripts[] = (object) array(
		'path'    => plugin_dir_url( __FILE__ ) . 'h5p-themer-config.js',
		'version' => '?buster=' . uniqid(),
	);
	$scripts[] = (object) array(
		'path'    => plugin_dir_url( __FILE__ ) . 'js/h5p-themer.js',
		'version' => '?ver=' . SNORDIANSH5PTHEMER_VERSION,
	);
}

register_activation_hook( __FILE__, 'Snordian\H5PThemer\on_activation' );
register_uninstall_hook( __FILE__, 'Snordian\H5PThemer\on_uninstall' );

add_action( 'h5p_alter_library_scripts', 'Snordian\H5PThemer\h5p_themer_alter_scripts', 10, 3 );
add_action( 'init', 'Snordian\H5PThemer\init' );
add_action( 'admin_notices', 'Snordian\H5PThemer\display_admin_messages' );
