<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_DB_Surgeon_Admin {

    const PAGE_SLUG = 'wp-db-surgeon';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_wpdbs_scan', array( $this, 'ajax_scan' ) );
        add_action( 'wp_ajax_wpdbs_clean', array( $this, 'ajax_clean' ) );
    }

    public function register_page(): void {
        add_submenu_page(
            'tools.php',
            __( 'WP DB Surgeon', 'wp-db-surgeon' ),
            __( 'DB Surgeon', 'wp-db-surgeon' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_page' )
        );
    }

    public function enqueue_assets( string $hook_suffix ): void {
        if ( 'tools_page_' . self::PAGE_SLUG !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'wp-db-surgeon',
            WP_DB_SURGEON_URL . 'admin/assets/admin.css',
            array(),
            WP_DB_SURGEON_VERSION
        );

        wp_enqueue_script(
            'wp-db-surgeon',
            WP_DB_SURGEON_URL . 'admin/assets/admin.js',
            array( 'jquery' ),
            WP_DB_SURGEON_VERSION,
            true
        );

        wp_localize_script(
            'wp-db-surgeon',
            'wpdbSurgeon',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wp_db_surgeon_nonce' ),
                'i18n'    => array(
                    'scanning' => __( 'Scanning...', 'wp-db-surgeon' ),
                    'cleaning' => __( 'Cleaning...', 'wp-db-surgeon' ),
                    'done'     => __( 'Done!', 'wp-db-surgeon' ),
                    'confirm'  => __( 'This will permanently delete selected items. Are you sure?', 'wp-db-surgeon' ),
                    'error'    => __( 'Something went wrong. Please try again.', 'wp-db-surgeon' ),
                ),
            )
        );
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission.', 'wp-db-surgeon' ) );
        }

        $scanner    = new WP_DB_Surgeon_Scanner();
        $report     = $scanner->get_report();
        $last_clean = get_option( 'wp_db_surgeon_last_clean' );

        include WP_DB_SURGEON_PATH . 'admin/views/main-page.php';
    }

    public function ajax_scan(): void {
        check_ajax_referer( 'wp_db_surgeon_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-db-surgeon' ) ) );
        }

        $scanner = new WP_DB_Surgeon_Scanner();
        $report  = $scanner->get_report();

        wp_send_json_success( $report );
    }

    public function ajax_clean(): void {
        check_ajax_referer( 'wp_db_surgeon_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-db-surgeon' ) ) );
        }

        $raw_items = isset( $_POST['items'] ) ? (array) $_POST['items'] : array();
        $items     = array_map( 'sanitize_key', $raw_items );

        $allowed = array( 'transients', 'revisions', 'auto_drafts', 'trash_posts', 'spam', 'trash_comments', 'orphaned_meta' );
        $items   = array_intersect( $items, $allowed );

        if ( empty( $items ) ) {
            wp_send_json_error( array( 'message' => __( 'No items selected.', 'wp-db-surgeon' ) ) );
        }

        $cleaner = new WP_DB_Surgeon_Cleaner();
        $results = $cleaner->clean( $items );

        wp_send_json_success( array(
            'results' => $results,
            'message' => __( 'Database cleaned successfully.', 'wp-db-surgeon' ),
        ) );
    }
}