<div class="wrap">
    <h1><?php esc_html_e( 'Le Post Client Settings', 'lepostclient' ); ?></h1>

    <?php 
    // Display admin notices stored in transient by the controller after save
    $settings_errors = get_transient('settings_errors');
    if ($settings_errors) {
        settings_errors('lepostclient_settings_notices', false, true); // slug, sanitize, hide_on_update=true (already handled)
        delete_transient('settings_errors');
    }
    ?>

    <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="lepostclient_save_settings">
        <?php wp_nonce_field( 'lepostclient_save_settings_action', 'lepostclient_settings_nonce_field' ); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="lepostclient_api_key"><?php esc_html_e( 'API Key', 'lepostclient' ); ?></label>
                    </th>
                    <td>
                        <?php 
                        $current_api_key = get_option(LePostClient\Settings\Manager::API_KEY_OPTION, '');
                        ?> 
                        <input type="text" id="lepostclient_api_key" name="lepostclient_api_key" value="<?php echo esc_attr( $current_api_key ); ?>" class="regular-text">
                        <p class="description">
                            <?php esc_html_e( 'Enter your API key to connect to Le Post Client service.', 'lepostclient' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lepostclient_company_info"><?php esc_html_e( 'Company Information', 'lepostclient' ); ?></label>
                    </th>
                    <td>
                        <?php 
                        $company_info = get_option(LePostClient\Settings\Manager::COMPANY_INFO_OPTION, '');
                        ?>
                        <textarea id="lepostclient_company_info" name="lepostclient_company_info" class="large-text" rows="5"><?php echo esc_textarea( $company_info ); ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Provide general information about the company. This will be used to guide AI content generation.', 'lepostclient' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lepostclient_writing_style"><?php esc_html_e( 'Writing Style', 'lepostclient' ); ?></label>
                    </th>
                    <td>
                        <?php 
                        $writing_style = get_option(LePostClient\Settings\Manager::WRITING_STYLE_OPTION, '');
                        ?>
                        <textarea id="lepostclient_writing_style" name="lepostclient_writing_style" class="large-text" rows="5"><?php echo esc_textarea( $writing_style ); ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Describe the desired writing style (e.g., formal, casual, witty, technical). This helps tailor the AI output.', 'lepostclient' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Settings', 'lepostclient' ) ); ?>
    </form>
</div> 