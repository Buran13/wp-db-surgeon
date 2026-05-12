<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_DB_Surgeon_Activator {

    public static function activate() {
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            wp_die(
                esc_html__( 'WP DB Surgeon requires PHP 8.0 or higher.', 'wp-db-surgeon' ),
                esc_html__( 'Plugin Activation Error', 'wp-db-surgeon' ),
                array( 'back_link' => true )
            );
        }

        $defaults = array(
            'schedule_enabled'  => false,
            'schedule_interval' => 'weekly',
            'clean_transients'  => true,
            'clean_revisions'   => true,
            'clean_autodrafts'  => true,
            'clean_trash'       => true,
            'clean_spam'        => true,
            'clean_orphaned'    => true,
        );

        add_option( 'wp_db_surgeon_settings', $defaults );
        update_option( 'wp_db_surgeon_version', WP_DB_SURGEON_VERSION );
        set_transient( 'wp_db_surgeon_activated', true, 30 );
    }
    
    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'wp_db_surgeon_scheduled_cleanup' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'wp_db_surgeon_scheduled_cleanup' );
        }

        delete_transient( 'wp_db_surgeon_activated' );
    }
}