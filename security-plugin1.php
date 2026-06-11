<?php
/**
 * Plugin Name:       Security Plugin 1
 * Plugin URI:        https://github.com/Teeeda112923/Security_plugin1
 * Description:       WordPressのセキュリティ設定を分かりやすく診断し、必要な対処を案内します。
 * Version:           0.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Teeeda112923
 * Text Domain:       security-plugin1
 *
 * @package Security_Plugin1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SP1_VERSION', '0.1.0' );
define( 'SP1_FILE', __FILE__ );
define( 'SP1_DIR', plugin_dir_path( __FILE__ ) );
define( 'SP1_URL', plugin_dir_url( __FILE__ ) );

require_once SP1_DIR . 'includes/class-sp1-diagnostics.php';
require_once SP1_DIR . 'includes/class-sp1-admin.php';

/**
 * Initializes the plugin.
 *
 * @return void
 */
function sp1_initialize_plugin() {
	$diagnostics = new SP1_Diagnostics();

	new SP1_Admin( $diagnostics );
}
add_action( 'plugins_loaded', 'sp1_initialize_plugin' );
