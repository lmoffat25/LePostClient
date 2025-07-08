<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Post Ideas List', 'lepostclient' ); ?></h1>
    <!-- <a href="#" class="page-title-action"><?php esc_html_e( 'Add New Idea (Example Button)', 'lepostclient' ); ?></a> -->
    <hr class="wp-header-end">

    <div id="lepc-ideas-grid-forms" style="display: flex; gap: 20px; margin-bottom: 20px;">
        
        <!-- Column 1: Add Post Idea Form -->
        <div class="postbox" style="flex: 1;">
            <h2 class="hndle" style="margin-left: 11px;"><span><?php esc_html_e( 'Add New Idea Manually', 'lepostclient' ); ?></span></h2>
            <div class="inside">
                <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="lepostclient_add_idea_manually">
                    <?php wp_nonce_field( 'lepc_add_idea_manually_action', 'lepc_add_idea_manually_nonce' ); ?>
                    
                    <p>
                        <label for="lepc_idea_subject_manual"><?php esc_html_e( 'Post Subject', 'lepostclient' ); ?></label>
                        <input type="text" id="lepc_idea_subject_manual" name="lepc_idea_subject_manual" class="widefat" value="">
                    </p>
                    <p>
                        <label for="lepc_idea_description_manual"><?php esc_html_e( 'Description', 'lepostclient' ); ?></label>
                        <textarea id="lepc_idea_description_manual" name="lepc_idea_description_manual" class="widefat" rows="4"></textarea>
                    </p>
                    <p>
                        <?php submit_button( __( 'Save Idea', 'lepostclient' ), 'primary', 'lepc_save_idea_manual_submit', false ); ?>
                    </p>
                </form>
            </div>
        </div>

        <!-- Column 2: Generate Ideas by AI Form -->
        <div class="postbox" style="flex: 1;">
            <h2 class="hndle"  style="margin-left: 11px;"><span><?php esc_html_e( 'Generate Ideas with AI', 'lepostclient' ); ?></span></h2>
            <div class="inside">
                <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="lepc-ai-generate-form">
                    <input type="hidden" name="action" value="lepostclient_generate_ideas_ai">
                    <?php wp_nonce_field( 'lepc_generate_ideas_ai_action', 'lepc_generate_ideas_ai_nonce_field' ); ?>
                    <p>
                        <label for="lepc_ai_subject"><?php esc_html_e( 'Base Subject / Keyword', 'lepostclient' ); ?></label>
                        <input type="text" id="lepc_ai_subject" name="lepc_ai_subject" class="widefat" value="">
                    </p>
                    <p>
                        <label for="lepc_ai_num_ideas"><?php esc_html_e( 'Number of Ideas to Generate', 'lepostclient' ); ?></label>
                        <select id="lepc_ai_num_ideas" name="lepc_ai_num_ideas" class="widefat">
                            <option value="1">1</option>
                            <option value="3" selected>3</option>
                            <option value="5">5</option>
                            <option value="10">10</option>
                        </select>
                    </p>
                    <p>
                        <?php submit_button( __( 'Generate Ideas', 'lepostclient' ), 'primary', 'lepc_generate_ideas_ai_submit', false ); ?>
                    </p>
                </form>
            </div>
        </div>

        <!-- Column 3: Drag and Drop CSV Form -->
        <div class="postbox" style="flex: 1;">
            <h2 class="hndle"  style="margin-left: 11px;"><span><?php esc_html_e( 'Import Ideas from CSV', 'lepostclient' ); ?></span></h2>
            <div class="inside">
                <div style="background-color: #f0f6fc; border-left: 4px solid #007cba; padding: 12px; margin-bottom: 15px;">
                    <p style="margin: 0 0 8px 0; font-size: 13px;">
                        • <?php esc_html_e( 'First column: Subject (required)', 'lepostclient' ); ?><br>
                        • <?php esc_html_e( 'Second column: Description (optional)', 'lepostclient' ); ?><br>
                    </p>
                </div>
                
                <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" id="lepc-csv-import-form">
                    <input type="hidden" name="action" value="lepostclient_import_csv">
                    <?php wp_nonce_field( 'lepostclient_import_csv_action', 'lepostclient_import_csv_nonce_field' ); ?>
                    <div id="lepc-csv-file-dropzone" style="border: 2px dashed #ccc; padding: 20px; text-align: center;">
                        <p><?php esc_html_e( 'Drag & Drop CSV file here or click to select.', 'lepostclient' ); ?></p>
                        <input type="file" id="lepc_csv_file" name="lepc_csv_file" accept=".csv" required>
                    </div>
                   
                    <p style="margin-top:15px;">
                        <?php submit_button( __( 'Import CSV', 'lepostclient' ), 'secondary', 'lepc_import_csv_submit', false ); ?>
                    </p>
                </form>
            </div>
        </div>
    </div>


    
    <!-- WordPress List Table for Post Ideas -->
    <form id="lepc-ideas-list-form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="lepostclient_bulk_actions">
        <?php wp_nonce_field( 'lepostclient_bulk_actions_nonce_action', 'lepostclient_bulk_actions_nonce_field' ); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'lepostclient' ); ?></label>
                <select name="bulk_action_top" id="bulk-action-selector-top">
                    <option value="-1"><?php esc_html_e( 'Bulk actions', 'lepostclient' ); ?></option>
                    <option value="bulk_generate"><?php esc_html_e( 'Generate Selected', 'lepostclient' ); ?></option>
                    <?php /* <option value="bulk_edit"><?php esc_html_e( 'Edit Selected', 'lepostclient' ); ?></option> */ ?>
                    <option value="bulk_delete"><?php esc_html_e( 'Delete Selected', 'lepostclient' ); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'lepostclient' ); ?>">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(esc_html__('%s items', 'lepostclient'), number_format_i18n($total_ideas)); ?></span>
                <?php 
                if (isset($total_pages) && $total_pages > 1) {
                    $page_links = paginate_links( [
                        'base' => add_query_arg( 'paged', '%#%' ),
                        'format' => '',
                        'prev_text' => __( '&laquo;', 'lepostclient' ),
                        'next_text' => __( '&raquo;', 'lepostclient' ),
                        'total' => $total_pages,
                        'current' => $current_page,
                        'add_args' => false,
                        'type' => 'array'
                    ]);
                    if ( $page_links ) {
                        echo '<span class="pagination-links">' . implode( ' ', $page_links ) . '</span>';
                    }
                } else {
                    // WordPress default style for one page or no items.
                    echo '<span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                    echo '<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">' . esc_html__('Current Page','lepostclient') . '</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> ' . esc_html__('of', 'lepostclient') . ' <span class="total-pages">1</span></span></span>';
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span></span>';
                }
                ?>
            </div>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped table-view-md posts">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'lepostclient' ); ?></label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" id="subject" class="manage-column column-subject column-primary sortable desc">
                        <a href="#"><span><?php esc_html_e( 'Subject', 'lepostclient' ); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" id="description" class="manage-column column-description">
                        <?php esc_html_e( 'Description', 'lepostclient' ); ?>
                    </th>
                    <th scope="col" id="status" class="manage-column column-status">
                        <?php esc_html_e( 'Status', 'lepostclient' ); ?>
                    </th>
                    <th scope="col" id="creation_date" class="manage-column column-date sortable asc">
                         <a href="#"><span><?php esc_html_e( 'Creation Date', 'lepostclient' ); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" id="actions" class="manage-column column-actions">
                        <?php esc_html_e( 'Actions', 'lepostclient' ); ?>
                    </th>
                </tr>
            </thead>

            <tbody id="the-list">
                <?php if ( isset($ideas) && ! empty( $ideas ) ) : ?>
                    <?php foreach ( $ideas as $idea ) : ?>
                        <?php 
                        $idea_id = (int) $idea->id;
                        $idea_subject_raw = $idea->subject; 
                        $idea_description_raw = $idea->description;
                        $idea_subject_display = esc_html( $idea->subject );
                        $idea_status = esc_html( $idea->status );
                        
                        // Format the date according to WordPress settings, ensuring proper localization
                        $date_format = get_option('date_format');
                        $time_format = get_option('time_format');
                        $idea_creation_date = esc_html(mysql2date("$date_format $time_format", $idea->creation_date));
                        
                        $can_generate = in_array($idea->status, ['pending', 'failed']);
                        $generated_post_id = $idea->generated_post_id ? (int) $idea->generated_post_id : null;
                        ?>
                        <tr id="post-idea-<?php echo $idea_id; ?>" class="iedit author-self level-0 post-idea-<?php echo $idea_id; ?> type-post status-<?php echo $idea_status; ?> hentry">
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo $idea_id; ?>"><?php printf(esc_html__( 'Select %s', 'lepostclient' ), $idea_subject_display); ?></label>
                                <input id="cb-select-<?php echo $idea_id; ?>" type="checkbox" name="post_idea_ids[]" value="<?php echo $idea_id; ?>">
                            </th>
                            <td class="subject column-subject has-row-actions column-primary" data-colname="Subject">
                                <strong><a class="row-title" href="#"><?php echo $idea_subject_display; ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="#" 
                                           class="lepc-open-edit-modal-button" 
                                           data-idea-id="<?php echo $idea_id; ?>" 
                                           data-subject="<?php echo esc_attr($idea_subject_raw); ?>" 
                                           data-description="<?php echo esc_attr($idea_description_raw); ?>" 
                                           aria-label="<?php printf(esc_attr__( 'Edit "%s"', 'lepostclient' ), $idea_subject_display); ?>">
                                            <?php esc_html_e( 'Edit', 'lepostclient' ); ?>
                                        </a> | 
                                    </span>
                                    <?php if ($can_generate): ?>
                                    <span class="inline lepc-generate-action"><button type="button" class="button-link lepc-open-generate-modal-button" data-idea-id="<?php echo $idea_id; ?>" data-subject="<?php echo esc_attr($idea_subject_raw); ?>" data-description="<?php echo esc_attr($idea_description_raw); ?>"><?php esc_html_e( 'Generate', 'lepostclient' ); ?></button> | </span>
                                    <?php elseif ($idea->status === 'generated' && $generated_post_id): ?>
                                    <span class="inline"><a href="<?php echo esc_url(get_edit_post_link($generated_post_id)); ?>" target="_blank"><?php esc_html_e('View Post', 'lepostclient'); ?></a> | </span>
                                    <?php endif; ?>
                                    <span class="trash">
                                        <a href="#" 
                                           class="submitdelete lepc-open-delete-modal-button" 
                                           data-idea-id="<?php echo $idea_id; ?>" 
                                           data-subject="<?php echo esc_attr($idea_subject_raw); ?>" 
                                           aria-label="<?php printf(esc_attr__( 'Delete "%s"', 'lepostclient' ), $idea_subject_display); ?>">
                                            <?php esc_html_e( 'Delete', 'lepostclient' ); ?>
                                        </a>
                                    </span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                            </td>
                            <td class="description column-description" data-colname="Description">
                                <?php echo esc_html(wp_trim_words($idea_description_raw, 20, '...')); ?>
                            </td>
                            <td class="status column-status" data-colname="Status">
                                <?php echo ucfirst($idea_status); ?>
                                <?php if ($idea->status === 'generating'): ?>
                                    <div class="lepc-small-progress-container">
                                        <div class="lepc-small-progress-bar" data-idea-id="<?php echo $idea_id; ?>"></div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="date column-date" data-colname="Creation Date">
                                <?php echo $idea_creation_date; ?>
                            </td>
                            <td class="actions column-actions" data-colname="Actions">
                                <?php if ($can_generate): ?>
                                <button type="button" class="button button-primary lepc-open-generate-modal-button" data-idea-id="<?php echo $idea_id; ?>" data-subject="<?php echo esc_attr($idea_subject_raw); ?>" data-description="<?php echo esc_attr($idea_description_raw); ?>"><?php esc_html_e( 'Generate', 'lepostclient' ); ?></button>
                                <?php elseif ($idea->status === 'generated' && $generated_post_id): ?>
                                    <a href="<?php echo esc_url(get_edit_post_link($generated_post_id)); ?>" class="button button-secondary" target="_blank"><?php esc_html_e('View Gen. Post', 'lepostclient'); ?></a>
                                <?php else: ?>
                                    <button type="button" class="button" disabled><?php echo esc_html(ucfirst($idea_status)); ?></button>
                                <?php endif; ?>
                                <button type="button" class="button button-secondary lepc-open-edit-modal-button" 
                                    data-idea-id="<?php echo $idea_id; ?>" 
                                    data-subject="<?php echo esc_attr($idea_subject_raw); ?>" 
                                    data-description="<?php echo esc_attr($idea_description_raw); ?>" 
                                    style="margin-left:5px;">
                                    <?php esc_html_e( 'Edit', 'lepostclient' ); ?>
                                </button>
                                <button type="button" class="button button-link-delete lepc-open-delete-modal-button" 
                                    data-idea-id="<?php echo $idea_id; ?>" 
                                    data-subject="<?php echo esc_attr($idea_subject_raw); ?>" 
                                    style="margin-left:5px; color: #d63638;">
                                    <?php esc_html_e( 'Delete', 'lepostclient' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e( 'No post ideas found.', 'lepostclient' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox"></td>
                    <th scope="col" class="manage-column column-subject column-primary sortable desc">
                        <a href="#"><span><?php esc_html_e( 'Subject', 'lepostclient' ); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-description">
                        <?php esc_html_e( 'Description', 'lepostclient' ); ?>
                    </th>
                    <th scope="col" id="status-foot" class="manage-column column-status">
                        <?php esc_html_e( 'Status', 'lepostclient' ); ?>
                    </th>
                    <th scope="col" class="manage-column column-date sortable asc">
                         <a href="#"><span><?php esc_html_e( 'Creation Date', 'lepostclient' ); ?></span><span class="sorting-indicator"></span></a>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php esc_html_e( 'Actions', 'lepostclient' ); ?>
                    </th>
                </tr>
            </tfoot>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                 <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'lepostclient' ); ?></label>
                <select name="bulk_action_bottom" id="bulk-action-selector-bottom">
                    <option value="-1"><?php esc_html_e( 'Bulk actions', 'lepostclient' ); ?></option>
                    <option value="bulk_generate"><?php esc_html_e( 'Generate Selected', 'lepostclient' ); ?></option>
                    <?php /* <option value="bulk_edit"><?php esc_html_e( 'Edit Selected', 'lepostclient' ); ?></option> */ ?>
                    <option value="bulk_delete"><?php esc_html_e( 'Delete Selected', 'lepostclient' ); ?></option>
                </select>
                <input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e( 'Apply', 'lepostclient' ); ?>">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(esc_html__('%s items', 'lepostclient'), number_format_i18n($total_ideas)); ?></span>
                <?php 
                if (isset($total_pages) && $total_pages > 1) {
                    if ( $page_links ) { // page_links should be available from top pagination
                        echo '<span class="pagination-links">' . implode( ' ', $page_links ) . '</span>';
                    }
                }  else {
                    // WordPress default style for one page or no items.
                    echo '<span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                    echo '<span class="paging-input"><label for="current-page-selector-bottom" class="screen-reader-text">' . esc_html__('Current Page','lepostclient') . '</label><input class="current-page" id="current-page-selector-bottom" type="text" name="paged_bottom" value="1" size="1" aria-describedby="table-paging-bottom"><span class="tablenav-paging-text"> ' . esc_html__('of', 'lepostclient') . ' <span class="total-pages">1</span></span></span>';
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                    echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span></span>';
                }
                ?>
            </div>
            <br class="clear">
        </div>
    </form>

    <!-- Generate Confirmation Modal -->
    <div id="lepc-generate-confirm-modal" class="lepc-modal" style="display:none;">
        <div class="lepc-modal-content">
            <h3 id="lepc-modal-title"><?php esc_html_e( 'Confirm Post Generation', 'lepostclient' ); ?></h3>
            <p id="lepc-modal-text"></p>
            
            <!-- Add progress bar container (initially hidden) -->
            <div id="lepc-modal-progress-container" class="lepc-progress-container" style="display:none;">
                <div id="lepc-modal-progress-bar" class="lepc-progress-bar">
                    <span id="lepc-modal-progress-text" class="lepc-progress-text">0%</span>
                </div>
            </div>
            
            <div class="lepc-modal-actions">
                <button type="button" id="lepc-modal-cancel-button" class="button"><?php esc_html_e( 'Cancel', 'lepostclient' ); ?></button>
                <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display: inline;" id="lepc-generate-modal-form">
                    <input type="hidden" name="action" value="lepostclient_initiate_generate_post">
                    <input type="hidden" id="lepc-modal-idea-id" name="idea_id" value="">
                    <input type="hidden" id="lepc-modal-idea-subject" name="idea_subject" value="">
                    <input type="hidden" id="lepc-modal-idea-description" name="idea_description" value="">
                    <?php wp_nonce_field('lepostclient_initiate_generate_post_action', 'lepostclient_initiate_generate_post_nonce'); ?>
                    <button type="submit" id="lepc-modal-confirm-generate-button" class="button button-primary"><?php esc_html_e( 'Confirm & Generate', 'lepostclient' ); ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Idea Modal -->
    <div id="lepc-edit-idea-modal" class="lepc-modal" style="display:none;">
        <div class="lepc-modal-content">
            <h3><?php esc_html_e( 'Edit Post Idea', 'lepostclient' ); ?></h3>
            <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="lepc-edit-idea-form">
                <input type="hidden" name="action" value="lepostclient_update_idea">
                <input type="hidden" id="lepc-edit-idea-id" name="idea_id" value="">
                <?php wp_nonce_field('lepostclient_update_idea_action', 'lepostclient_update_idea_nonce'); ?>
                
                <p>
                    <label for="lepc-edit-idea-subject"><?php esc_html_e( 'Post Subject', 'lepostclient' ); ?></label>
                    <input type="text" id="lepc-edit-idea-subject" name="idea_subject" class="widefat" value="">
                </p>
                <p>
                    <label for="lepc-edit-idea-description"><?php esc_html_e( 'Description', 'lepostclient' ); ?></label>
                    <textarea id="lepc-edit-idea-description" name="idea_description" class="widefat" rows="4"></textarea>
                </p>
                <div class="lepc-modal-actions">
                    <button type="button" id="lepc-edit-modal-cancel-button" class="button"><?php esc_html_e( 'Cancel', 'lepostclient' ); ?></button>
                    <button type="submit" id="lepc-edit-modal-save-button" class="button button-primary"><?php esc_html_e( 'Save Changes', 'lepostclient' ); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="lepc-delete-confirm-modal" class="lepc-modal" style="display:none;">
        <div class="lepc-modal-content">
            <h3 id="lepc-delete-modal-title"><?php esc_html_e( 'Confirm Deletion', 'lepostclient' ); ?></h3>
            <p id="lepc-delete-modal-text"></p>
            <div class="lepc-modal-actions">
                <button type="button" id="lepc-delete-modal-cancel-button" class="button"><?php esc_html_e( 'Cancel', 'lepostclient' ); ?></button>
                <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display: inline;" id="lepc-delete-modal-form">
                    <input type="hidden" name="action" value="lepostclient_delete_idea">
                    <input type="hidden" id="lepc-delete-modal-idea-id" name="idea_id" value="">
                    <?php wp_nonce_field('lepostclient_delete_idea_action', 'lepostclient_delete_idea_nonce'); ?>
                    <button type="submit" id="lepc-delete-modal-confirm-button" class="button button-primary button-link-delete" style="background-color: #d63638; border-color: #d63638; color: white;"><?php esc_html_e( 'Confirm & Delete', 'lepostclient' ); ?></button>
                </form>
            </div>
        </div>
    </div>

</div> 