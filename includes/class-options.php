<?php
/**
 * Options page for the plugin.
 *
 * @package snordians-h5p-themer
 */

namespace Snordian\H5PThemer;

// as suggested by the WordPress community.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Options page for the plugin.
 *
 * @package snordians-h5p-themer
 */
class Options {

	/**
	 * Default schedule for automated updates.
	 *
	 * @var string
	 */
	const DEFAULT_UPDATE_SCHEDULE = 'never';

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	private static $option_slug = 'snordiansh5pthemer_option';

	/**
	 * Options.
	 *
	 * @var array
	 */
	private static $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue JS/CSS only on this plugin's settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_snordiansh5pthemer-admin' !== $hook_suffix ) {
			return;
		}

		$plugin_file = dirname( __DIR__ ) . '/snordians-h5p-themer.php';
		$script_url  = plugins_url( 'js/h5p-theme-picker-loader.js', $plugin_file );

		wp_enqueue_script_module(
			'snordians-h5p-themer-options',
			$script_url,
			array(),
			SNORDIANSH5PTHEMER_VERSION
		);
		wp_script_add_data( 'snordians-h5p-themer-options', 'type', 'module' );
	}

	/**
	 * Get the option slug.
	 *
	 * @return string Option slug.
	 */
	public static function get_slug() {
		return self::$option_slug;
	}

	/**
	 * Delete options.
	 */
	public static function delete_options() {
		delete_option( self::$option_slug );
		delete_site_option( self::$option_slug );
	}

	/**
	 * Add options page.
	 */
	public function add_plugin_page() {
		// This page will be under "Settings".
		add_options_page(
			'Settings Admin',
			'H5P Themer',
			'manage_options',
			'snordiansh5pthemer-admin',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback.
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h2><?php echo esc_html( __( 'H5P Themer', 'snordians-h5p-themer' ) ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'snordiansh5pthemer_option_group' );
				?>

		<div class="snordians-h5p-themer-settings">
			<?php $this->theme_picker_callback(); ?>
		</div>

		<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings.
	 */
	public function page_init() {
		// The `sanitize` function properly sanitizes all input.
		register_setting(
			'snordiansh5pthemer_option_group',
			'snordiansh5pthemer_option',
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys.
	 * @return array Output.
	 */
	public function sanitize( $input ) {
		$input = (array) $input;

		$new_input                  = array();
		$new_input['picker_values'] = empty( $input['picker_values'] ) ?
			'' :
			sanitize_text_field( $input['picker_values'] );

		return $new_input;
	}

	/**
	 * Get update schedule option callback.
	 */
	public function theme_picker_callback() {
		$current_value_json_string = self::get_theme_picker();

		$parsed = json_decode( $current_value_json_string, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $parsed ) ) {
			$parsed = array();
		}

		$theme   = isset( $parsed['theme'] ) ? (string) $parsed['theme'] : '';
		$density = isset( $parsed['data']['density'] ) ? (string) $parsed['data']['density'] : '';

		if ( 'custom' === $theme ) {
			$colors = isset( $parsed['data']['colors'] ) && is_array( $parsed['data']['colors'] )
			? $parsed['data']['colors']
			: array();

			$buttons     = isset( $colors['--h5p-theme-main-cta-base'] ) ? (string) $colors['--h5p-theme-main-cta-base'] : '';
			$navigation  = isset( $colors['--h5p-theme-secondary-cta-base'] ) ? (string) $colors['--h5p-theme-secondary-cta-base'] : '';
			$alternative = isset( $colors['--h5p-theme-alternative-base'] ) ? (string) $colors['--h5p-theme-alternative-base'] : '';
			$background  = isset( $colors['--h5p-theme-background'] ) ? (string) $colors['--h5p-theme-background'] : '';
		}
		?>
	<h5p-theme-picker
		<?php echo $theme ? 'theme-name="' . esc_attr( $theme ) . '"' : ''; ?>
		<?php echo $density ? 'density="' . esc_attr( $density ) . '"' : ''; ?>
		<?php echo 'custom' === $theme && $buttons ? 'custom-color-buttons="' . esc_attr( $buttons ) . '"' : ''; ?>
		<?php echo 'custom' === $theme && $navigation ? 'custom-color-navigation="' . esc_attr( $navigation ) . '"' : ''; ?>
		<?php echo 'custom' === $theme && $alternative ? 'custom-color-alternative="' . esc_attr( $alternative ) . '"' : ''; ?>
		<?php echo 'custom' === $theme && $background ? 'custom-color-background="' . esc_attr( $background ) . '"' : ''; ?>
	>

	</h5p-theme-picker>
	<input
			name="snordiansh5pthemer_option[picker_values]"
			type="text"
			id="picker_values"
			style="display: none;"
			value="<?php echo esc_attr( self::get_theme_picker() ); ?>"
		/>
		<?php
	}

	/**
	 * Get picker values
	 *
	 * @return string picker values.
	 */
	public static function get_theme_picker() {
		return ( isset( self::$options['picker_values'] ) ) ?
			self::$options['picker_values'] :
			'';
	}

	/**
	 * Init function for the class.
	 */
	public static function init() {
		self::$options = get_option( self::$option_slug, false );
	}
}
Options::init();
