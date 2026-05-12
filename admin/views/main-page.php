<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_items = array_sum( array_column( $report, 'count' ) );
?>

<div class="wrap" id="wp-db-surgeon">

    <h1><?php esc_html_e( 'WP DB Surgeon', 'wp-db-surgeon' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Preview what will be removed, then clean with one click.', 'wp-db-surgeon' ); ?>
    </p>

    <?php if ( $last_clean ) : ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <?php
            printf(
                esc_html__( 'Last cleanup: %s ago.', 'wp-db-surgeon' ),
                esc_html( human_time_diff( $last_clean['timestamp'] ) )
            );
            ?>
        </p>
    </div>
    <?php endif; ?>
    <div class="wpdbs-card">

        <?php if ( 0 === $total_items ) : ?>
            <div class="wpdbs-empty">
                <span class="dashicons dashicons-yes-alt"></span>
                <p><?php esc_html_e( 'Your database looks clean. Nothing to remove.', 'wp-db-surgeon' ); ?></p>
            </div>

        <?php else : ?>

            <form id="wpdbs-form">
                <table class="wpdbs-table widefat">
                    <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" id="wpdbs-select-all">
                            </th>
                            <th><?php esc_html_e( 'Item', 'wp-db-surgeon' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'wp-db-surgeon' ); ?></th>
                            <th><?php esc_html_e( 'Count', 'wp-db-surgeon' ); ?></th>
                            <th><?php esc_html_e( 'Size', 'wp-db-surgeon' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $report as $key => $item ) : ?>
                        <tr class="<?php echo $item['count'] > 0 ? 'has-items' : 'no-items'; ?>" data-key="<?php echo esc_attr( $key ); ?>">
                            <td class="check-column">
                                <?php if ( $item['count'] > 0 ) : ?>
                                <input type="checkbox" name="items[]" value="<?php echo esc_attr( $key ); ?>" checked>
                                <?php else : ?>
                                <input type="checkbox" disabled>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $item['label'] ); ?></td>
                            <td><?php echo esc_html( $item['description'] ); ?></td>
                            <td>
                                <span class="wpdbs-badge <?php echo $item['count'] === 0 ? 'wpdbs-badge--clean' : ''; ?>">
                                    <?php echo esc_html( number_format_i18n( $item['count'] ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $item['size'] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="wpdbs-actions">
                    <button type="button" id="wpdbs-clean-btn" class="button button-primary button-large">
                        <?php
                        printf(
                            esc_html__( 'Clean selected (%s items)', 'wp-db-surgeon' ),
                            esc_html( number_format_i18n( $total_items ) )
                        );
                        ?>
                    </button>
                    <span class="spinner wpdbs-spinner"></span>
                    <span id="wpdbs-status" class="wpdbs-status"></span>
                </div>

            </form>

        <?php endif; ?>

    </div>

</div>