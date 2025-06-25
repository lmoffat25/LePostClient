<div class="lepc-api-key-setup-overlay">
    <div class="lepc-api-key-setup-container">
        <div class="lepc-api-key-setup-header">
            <h1><?php esc_html_e('Welcome to Le Post Client', 'lepostclient'); ?></h1>
        </div>
        
        <div class="lepc-api-key-setup-content">
            <p class="lepc-api-key-setup-subtitle">
                <?php esc_html_e('To get started, please enter your API key below. This key is required to connect to the Le Post service and generate content.', 'lepostclient'); ?>
            </p>
            
            <?php 
            // Display admin notices stored in transient
            $settings_errors = get_transient('settings_errors');
            if ($settings_errors) {
                settings_errors('lepostclient_api_key_setup_notices', false, true);
                delete_transient('settings_errors');
            }
            ?>
            
            <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="lepc-api-key-setup-form">
                <input type="hidden" name="action" value="lepostclient_save_api_key_setup">
                <?php wp_nonce_field('lepostclient_save_api_key_action', 'lepostclient_api_key_setup_nonce'); ?>
                
                <div class="lepc-api-key-setup-field">
                    <label for="lepostclient_api_key"><?php esc_html_e('API Key', 'lepostclient'); ?></label>
                    <input type="text" id="lepostclient_api_key" name="lepostclient_api_key" class="regular-text" value="" required>
                    <p class="description">
                        <?php 
                        printf(
                            /* translators: %s: URL to retrieve API key */
                            esc_html__('Don\'t have an API key? Get one from %s', 'lepostclient'),
                            '<a href="https://agence-web-prism.fr/mon-compte/" target="_blank">' . esc_html__('your account page', 'lepostclient') . '</a>'
                        ); 
                        ?>
                    </p>
                </div>
                
                <div class="lepc-api-key-setup-actions">
                    <?php submit_button(__('Save API Key', 'lepostclient'), 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
    </div>
</div> 