<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_DB_Surgeon_Scanner {

    private wpdb $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function get_report(): array {
        return array(
            'transients'     => $this->scan_transients(),
            'revisions'      => $this->scan_revisions(),
            'auto_drafts'    => $this->scan_auto_drafts(),
            'trash_posts'    => $this->scan_trash_posts(),
            'spam'           => $this->scan_spam_comments(),
            'trash_comments' => $this->scan_trash_comments(),
            'orphaned_meta'  => $this->scan_orphaned_postmeta(),
        );
    }

    private function scan_transients(): array {
        $count = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->options}
                WHERE option_name LIKE %s
                AND option_value < %d",
                '_transient_timeout_%',
                time()
            )
        );

        $size = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT SUM( LENGTH(option_value) ) FROM {$this->db->options}
                WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_%',
                '_site_transient_%'
            )
        );

        return array(
            'count'       => $count,
            'size'        => $this->format_bytes( $size ),
            'label'       => __( 'Expired transients', 'wp-db-surgeon' ),
            'description' => __( 'Temporary cache data left by plugins.', 'wp-db-surgeon' ),
        );
    }

    private function scan_revisions(): array {
        $count = (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->posts}
            WHERE post_type = 'revision'"
        );

        $size = (int) $this->db->get_var(
            "SELECT SUM( LENGTH(post_content) ) FROM {$this->db->posts}
            WHERE post_type = 'revision'"
        );

        return array(
            'count'       => $count,
            'size'        => $this->format_bytes( $size ),
            'label'       => __( 'Post revisions', 'wp-db-surgeon' ),
            'description' => __( 'Old versions of posts saved automatically.', 'wp-db-surgeon' ),
        );
    }

    private function scan_auto_drafts(): array {
        $count = (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->posts}
            WHERE post_status = 'auto-draft'"
        );

        return array(
            'count'       => $count,
            'size'        => '—',
            'label'       => __( 'Auto drafts', 'wp-db-surgeon' ),
            'description' => __( 'Unsaved drafts created when you open the editor.', 'wp-db-surgeon' ),
        );
    }

    private function scan_trash_posts(): array {
        $count = (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->posts}
            WHERE post_status = 'trash'"
        );

        return array(
            'count'       => $count,
            'size'        => '—',
            'label'       => __( 'Trashed posts & pages', 'wp-db-surgeon' ),
            'description' => __( 'Content moved to trash but not deleted.', 'wp-db-surgeon' ),
        );
    }

    private function scan_spam_comments(): array {
        $count = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->comments}
                WHERE comment_approved = %s",
                'spam'
            )
        );

        return array(
            'count'       => $count,
            'size'        => '—',
            'label'       => __( 'Spam comments', 'wp-db-surgeon' ),
            'description' => __( 'Comments marked as spam.', 'wp-db-surgeon' ),
        );
    }

    private function scan_trash_comments(): array {
        $count = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->comments}
                WHERE comment_approved = %s",
                'trash'
            )
        );

        return array(
            'count'       => $count,
            'size'        => '—',
            'label'       => __( 'Trashed comments', 'wp-db-surgeon' ),
            'description' => __( 'Comments moved to trash.', 'wp-db-surgeon' ),
        );
    }

    private function scan_orphaned_postmeta(): array {
        $count = (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->postmeta} pm
            LEFT JOIN {$this->db->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL"
        );

        return array(
            'count'       => $count,
            'size'        => '—',
            'label'       => __( 'Orphaned post meta', 'wp-db-surgeon' ),
            'description' => __( 'Metadata whose parent post no longer exists.', 'wp-db-surgeon' ),
        );
    }

    private function format_bytes( int $bytes ): string {
        if ( $bytes <= 0 ) {
            return '0 B';
        }

        $units = array( 'B', 'KB', 'MB', 'GB' );
        $power = floor( log( $bytes, 1024 ) );
        $power = min( $power, count( $units ) - 1 );

        return round( $bytes / ( 1024 ** $power ), 2 ) . ' ' . $units[ $power ];
    }
}