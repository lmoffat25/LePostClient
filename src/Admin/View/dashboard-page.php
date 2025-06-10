<div class="wrap lepc-dashboard">
    <h1><?php esc_html_e( 'Le Post Client Dashboard', 'lepostclient' ); ?></h1>
    <p><?php esc_html_e( 'Welcome to your dashboard. Here you can find a summary of your account and quick actions.', 'lepostclient' ); ?></p>
    <hr/>

    <div class="lepc-dashboard-grid">
        <!-- Column 1: Credits -->
        <div class="postbox">
            <h2 class="hndle"><span><span class="dashicons dashicons-database-view" style="margin-right: 8px;"></span><?php esc_html_e( 'Your Credits', 'lepostclient' ); ?></span></h2>
            <div class="inside">
                <?php if ( ! empty( $error_message ) ) : ?>
                    <div class="notice notice-error is-dismissible inline">
                        <p>
                            <?php echo esc_html( $error_message ); ?>
                            <?php if ( str_contains( strtolower($error_message), 'api key' ) ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=lepostclient_settings' ) ); ?>"><?php esc_html_e( 'Go to Settings', 'lepostclient' ); ?></a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php elseif ( isset( $account_info ) ) : ?>
                    <div class="lepc-credits-display">
                        <span class="lepc-credits-number"><?php echo esc_html( number_format_i18n( $account_info['credits'] ?? 0 ) ); ?></span>
                        <span class="lepc-credits-label"><?php esc_html_e( 'Credits Remaining', 'lepostclient' ); ?></span>
                    </div>
                    <p class="description">
                        <?php 
                        if(isset($account_info['account']['plan']['monthly_limit'])) {
                             printf(
                                esc_html__('You have a monthly limit of %s credits.', 'lepostclient'), 
                                esc_html(number_format_i18n($account_info['account']['plan']['monthly_limit']))
                            );
                        }
                        ?>
                    </p>
                    <a href="https://app.le-post.com/shop" class="button button-primary" target="_blank" style="margin-top:15px;"><?php esc_html_e( 'Buy More Credits', 'lepostclient' ); ?></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Column 2: Placeholder -->
        <div class="postbox">
            <h2 class="hndle"><span><span class="dashicons dashicons-chart-line" style="margin-right: 8px;"></span><?php esc_html_e( 'Usage Statistics', 'lepostclient' ); ?></span></h2>
            <div class="inside">
                <p><?php esc_html_e( 'Usage charts and statistics will be displayed here in a future update.', 'lepostclient' ); ?></p>
                
            </div>
        </div>

        <!-- Column 3: Placeholder -->
        <div class="postbox">
            <h2 class="hndle"><span><span class="dashicons dashicons-admin-links" style="margin-right: 8px;"></span><?php esc_html_e( 'Quick Links', 'lepostclient' ); ?></span></h2>
            <div class="inside">
                <ul>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=lepostclient_ideas_list')); ?>"><?php esc_html_e('Go to Ideas List', 'lepostclient'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=lepostclient_settings')); ?>"><?php esc_html_e('Configure Settings', 'lepostclient'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .lepc-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .lepc-credits-display {
        text-align: center;
        padding: 20px;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        background: #f6f7f7;
    }
    .lepc-credits-number {
        display: block;
        font-size: 3em;
        font-weight: bold;
        line-height: 1.2;
    }
    .lepc-credits-label {
        font-size: 1.2em;
        color: #50575e;
    }
</style> 