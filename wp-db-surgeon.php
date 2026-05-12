<?php
/**
 * Plugin Name:       WP DB Surgeon
 * Plugin URI:        https://github.com/Buran13/wp-db-surgeon
 * Description:       Lightweight WordPress database cleaner. Preview before delete, scheduled cleanup, no paywalls.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        Buran / Tern-web
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-db-surgeon
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_DB_SURGEON_VERSION', '0.1.0' );
define( 'WP_DB_SURGEON_FILE', __FILE__ );
define( 'WP_DB_SURGEON_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_DB_SURGEON_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function( $class_name ) {
    if ( strpos( $class_name, 'WP_DB_Surgeon' ) !== 0 ) {
        return;
    }

    $file = strtolower( str_replace(
        array( 'WP_DB_Surgeon_', '_' ),
        array( '', '-' ),
        $class_name
    ) );

    $locations = array(
        WP_DB_SURGEON_PATH . 'includes/class-' . $file . '.php',
        WP_DB_SURGEON_PATH . 'admin/class-' . $file . '.php',
    );

    foreach ( $locations as $path ) {
        if ( file_exists( $path ) ) {
            require_once $path;
            return;
        }
    }
} );

add_action( 'plugins_loaded', 'wp_db_surgeon_init' );

function wp_db_surgeon_init() {
    if ( is_admin() ) {

    }
}

register_activation_hook( WP_DB_SURGEON_FILE, array( 'WP_DB_Surgeon_Activator', 'activate' ) );
register_deactivation_hook( WP_DB_SURGEON_FILE, array( 'WP_DB_Surgeon_Activator', 'deactivate' ) );