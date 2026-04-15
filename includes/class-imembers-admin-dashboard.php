<?php
/**
 * Admin Dashboard class for iMembers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Admin_Dashboard {

    public function init() {
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
    }

    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'imembers_status_widget',
            'iMembers 概要',
            array( $this, 'render_dashboard_widget' )
        );
    }

    public function render_dashboard_widget() {
        // Today's new members
        $today_query = new WP_User_Query( array(
            'date_query' => array(
                array( 'after' => 'today', 'inclusive' => true )
            ),
            'count_total' => true,
        ) );
        $new_members_count = $today_query->get_total();

        // Pro subscribers count
        $pro_query = new WP_User_Query( array(
            'meta_key' => '_imembers_pro_subscriber',
            'meta_value' => '1',
            'count_total' => true,
        ) );
        $pro_members_count = $pro_query->get_total();

        ?>
        <div class="imembers-dashboard-widget">
            <p style="font-size: 1.2em;">
                <span class="dashicons dashicons-id"></span> 本日の新規登録会員: <strong><?php echo intval( $new_members_count ); ?></strong> 名
            </p>
            <p style="font-size: 1.2em;">
                <span class="dashicons dashicons-star-filled" style="color: #f1c40f;"></span> 現在の有料会員数: <strong><?php echo intval( $pro_members_count ); ?></strong> 名
            </p>
            <hr>
            <p>
                <a href="<?php echo admin_url('users.php'); ?>" class="button button-primary">会員一覧を表示</a>
                <a href="<?php echo admin_url('admin.php?page=imembers-settings'); ?>" class="button">設定画面へ</a>
            </p>
        </div>
        <style>
            .imembers-dashboard-widget .dashicons {
                margin-right: 8px;
                vertical-align: middle;
            }
        </style>
        <?php
    }
}
