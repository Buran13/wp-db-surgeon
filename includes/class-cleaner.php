<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_DB_Surgeon_Cleaner {

    private wpdb $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function clean( array $items ): array {
        $results = array();

        $handlers = array(
            'transients'     => 'clean_transients',
            'revisions'      => 'clean_revisions',
            'auto_drafts'    => 'clean_auto_drafts',
            'trash_posts'    => 'clean_trash_posts',
            'spam'           => 'clean_spam_comments',
            'trash_comments' => 'clean_trash_comments',
            'orphaned_meta'  => 'clean_orphaned_postmeta',
        );

        foreach ( $items as $item ) {
            if ( isset( $handlers[ $item ] ) ) {
                $method          = $handlers[ $item ];
                $results[ $item ] = $this->$method();
            }
        }

        update_option( 'wp_db_surgeon_last_clean', array(
            'timestamp' => time(),
            'results'   => $results,
        ) );

        return $results;
    }

    private function clean_transients(): int {
        $count = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->options}
                WHERE option_name LIKE %s AND option_value < %d",
                '_transient_timeout_%',
                time()
            )
        );

        delete_expired_transients( true );

        return $count;
    }

    private function clean_revisions(): int {
        $revisions = $this->db->get_col(
            "SELECT ID FROM {$this->db->posts}
            WHERE post_type = 'revision'"
        );

        foreach ( $revisions as $id ) {
            wp_delete_post( (int) $id, true );
        }

        return count( $revisions );
    }

    private function clean_auto_drafts(): int {
        $drafts = $this->db->get_col(
            "SELECT ID FROM {$this->db->posts}
            WHERE post_status = 'auto-draft'"
        );

        foreach ( $drafts as $id ) {
            wp_delete_post( (int) $id, true );
        }

        return count( $drafts );
    }

    private function clean_trash_posts(): int {
        $posts = $this->db->get_col(
            "SELECT ID FROM {$this->db->posts}
            WHERE post_status = 'trash'"
        );

        foreach ( $posts as $id ) {
            wp_delete_post( (int) $id, true );
        }

        return count( $posts );
    }

    private function clean_spam_comments(): int {
        $comments = $this->db->get_col(
            $this->db->prepare(
                "SELECT comment_ID FROM {$this->db->comments}
                WHERE comment_approved = %s",
                'spam'
            )
        );

        foreach ( $comments as $id ) {
            wp_delete_comment( (int) $id, true );
        }

        return count( $comments );
    }

    private function clean_trash_comments(): int {
        $comments = $this->db->get_col(
            $this->db->prepare(
                "SELECT comment_ID FROM {$this->db->comments}
                WHERE comment_approved = %s",
                'trash'
            )
        );

        foreach ( $comments as $id ) {
            wp_delete_comment( (int) $id, true );
        }

        return count( $comments );
    }

    private function clean_orphaned_postmeta(): int {
        $count = (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->postmeta} pm
            LEFT JOIN {$this->db->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL"
        );

        $this->db->query(
            "DELETE pm FROM {$this->db->postmeta} pm
            LEFT JOIN {$this->db->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL"
        );

        return $count;
    }
}