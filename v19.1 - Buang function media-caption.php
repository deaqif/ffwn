<?php
/**
 * Plugin Name: Fluent Forms WhatsApp Notification
 * Description: Sends a WhatsApp notification from a Fluent Form submission with media support.
 * Version: 19.1 (Buang media-caption)
 * Author: Malek Md Som
 * Author URI: https://webbku.com
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. DAFTARKAN MENU ADMIN
add_action('admin_menu', 'wfn_snippet_admin_menu');
function wfn_snippet_admin_menu() {
    add_submenu_page('fluent_forms', 'WhatsApp Notifications', 'WhatsApp Notifications', 'manage_options', 'wfn_settings_snippet', 'wfn_snippet_render_page');
}

// 2. DAFTARKAN TETAPAN
add_action('admin_init', 'wfn_snippet_register_settings');
function wfn_snippet_register_settings() {
    register_setting('wfn_settings_group_snippet', 'wfn_settings_rules', ['sanitize_callback' => 'wfn_snippet_sanitize_rules']);
}

// 3. FUNGSI UTAMA UNTUK MEMAPARKAN HALAMAN
function wfn_snippet_render_page() {
    if (!current_user_can('manage_options')) return;
    
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'wasapbom';
    
    $api_key = get_option('wfn_api_key_wasapbom', '');
    $placeholder = !empty($api_key) ? '••••••••••••••••••••••••••••••' : 'Enter your Wasapbom API Key';
    ?>
    <style>
        .wfn-container {
            display: flex;
            background: #fff;
            border: 1px solid #ccd0d4;
            margin-top: 20px;
        }
        .wfn-sidebar {
            width: 200px;
            background: #f8f9fa;
            border-right: 1px solid #e5e5e5;
            min-height: 500px;
        }
        .wfn-sidebar ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .wfn-sidebar li {
            border-bottom: 1px solid #e5e5e5;
        }
        .wfn-sidebar a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .wfn-sidebar a:hover {
            background-color: #e9ecef;
        }
        .wfn-sidebar a.active {
            background-color: #007cba;
            color: #fff;
        }
        .wfn-sidebar a.active:hover {
            background-color: #005a87;
        }
        .wfn-sidebar .dashicons {
            margin-right: 8px;
        }
        .wfn-content {
            flex: 1;
            padding: 30px;
        }
        .wfn-api-key-wrapper { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .wfn-save-notice { 
            font-weight: 600; 
        }
        .wfn-save-notice.success { 
            color: #008a20; 
        }
        .wfn-rule-row { 
            background: #fff; 
            border: 1px solid #ccd0d4; 
            padding: 20px; 
            margin-top: 20px; 
        }
        .wfn-rule-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-bottom: 10px; 
            border-bottom: 1px solid #e0e0e0; 
            margin-bottom: 15px; 
        }
        .wfn-rule-header h4 { 
            margin: 0; 
            flex-grow: 1; 
        }
        .wfn-delete-rule-button { 
            font-size: 28px; 
            font-weight: 600; 
            color: #9ca3af; 
            text-decoration: none; 
            line-height: 1; 
            transition: color 0.2s; 
            padding-left: 20px; 
        }
        .wfn-delete-rule-button:hover { 
            color: #dc2626; 
        }
        .wfn-media-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        .wfn-media-section h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .wfn-media-toggle {
            margin-bottom: 15px;
        }
        .wfn-media-options {
            display: none;
        }
        .wfn-media-options.active {
            display: block;
        }
        .wfn-media-type-selector {
            margin-bottom: 15px;
        }
        .wfn-media-url-input {
            margin-bottom: 10px;
        }
        .wfn-media-caption {
            margin-top: 10px;
        }
        .wfn-media-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
            font-size: 12px;
            color: #1976d2;
        }
        #wfn-shortcode-popover { 
            display: none; 
            position: absolute; 
            z-index: 1000; 
            background: #fff; 
            border: 1px solid #e5e5e5; 
            box-shadow: 0 3px 8px rgba(0,0,0,0.15); 
            border-radius: 6px; 
            width: 380px; 
        }
        #wfn-shortcode-popover::after { 
            content: ''; 
            position: absolute; 
            bottom: -6px; 
            left: 50%; 
            transform: translateX(-50%); 
            width: 0; 
            height: 0; 
            border-left: 6px solid transparent; 
            border-right: 6px solid transparent; 
            border-top: 6px solid #fff; 
        }
        #wfn-shortcode-popover ul { 
            list-style: none; 
            margin: 0; 
            padding: 0; 
            max-height: 250px; 
            overflow-y: auto; 
        }
        #wfn-shortcode-popover li { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px 15px; 
            cursor: pointer; 
            border-bottom: 1px solid #f0f0f1; 
        }
        #wfn-shortcode-popover li:last-child { 
            border-bottom: none; 
        }
        #wfn-shortcode-popover li:hover { 
            background-color: #f0f6fc; 
        }
        #wfn-shortcode-popover li code { 
            color: #50575e; 
            font-family: monospace; 
        }
        #wfn-shortcode-popover .message { 
            padding: 15px; 
            color: #50575e; 
            text-align: center; 
        }
        .wfn-tab-content {
            display: none;
        }
        .wfn-tab-content.active {
            display: block;
        }
        .wfn-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .wfn-status-badge.connected {
            background-color: #d4edda;
            color: #155724;
        }
        .wfn-status-badge.disconnected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .wfn-info-box {
            background: #f0f6fc;
            border: 1px solid #c6d9f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .wfn-info-box h3 {
            margin-top: 0;
            color: #0073aa;
        }
        .wfn-section {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .wfn-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #23282d;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 10px;
        }
        .wfn-coming-soon {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
    
    <div class="wrap">
        <h1>WhatsApp Notifications</h1>
        
        <div class="wfn-container">
            <div class="wfn-sidebar">
                <ul>
                    <li>
                        <a href="?page=wfn_settings_snippet&tab=wasapbom" class="<?php echo $current_tab === 'wasapbom' ? 'active' : ''; ?>">
                            <span class="dashicons dashicons-smartphone"></span>
                            Wasapbom
                        </a>
                    </li>
                    <li>
                        <a href="?page=wfn_settings_snippet&tab=wasapbot" class="<?php echo $current_tab === 'wasapbot' ? 'active' : ''; ?>">
                            <span class="dashicons dashicons-admin-generic"></span>
                            Wasapbot
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="wfn-content">
                <!-- Wasapbom Tab -->
                <div class="wfn-tab-content <?php echo $current_tab === 'wasapbom' ? 'active' : ''; ?>">
                    <h2>Wasapbom Configuration</h2>
                    
                    <!-- API Key Section -->
                    <div class="wfn-section">
                        <h3>API Credentials</h3>
                        
                        <div class="wfn-info-box">
                            <h3>Connection Status</h3>
                            <p>
                                Wasapbom API: 
                                <span class="wfn-status-badge <?php echo !empty($api_key) ? 'connected' : 'disconnected'; ?>">
                                    <?php echo !empty($api_key) ? 'Connected' : 'Disconnected'; ?>
                                </span>
                            </p>
                            <p>Visit the <a href="https://panel.wasapbom.com" target="_blank">Wasapbom Console</a> to get your API key.</p>
                        </div>
                        
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="wfn_api_key_field">Access API Key</label>
                                    </th>
                                    <td>
                                        <div class="wfn-api-key-wrapper">
                                            <input type="password" id="wfn_api_key_field" value="<?php echo esc_attr($placeholder); ?>" class="regular-text" autocomplete="new-password">
                                            <button type="button" id="wfn-save-api-key-button" class="button button-secondary">Save API Key</button>
                                            <span id="wfn-api-key-save-notice" class="wfn-save-notice" style="display: none;"></span>
                                        </div>
                                        <p class="description">
                                            <?php if (!empty($api_key)): ?>
                                                <span class="wfn-status-badge connected">API Key verified successfully</span>
                                            <?php else: ?>
                                                Enter your API Key from Wasapbom Console
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Notification Rules Section -->
                    <div class="wfn-section">
                        <h3>Notification Rules</h3>
                        
                        <form action="options.php" method="post">
                            <?php settings_fields('wfn_settings_group_snippet'); ?>
                            <div id="wfn-rules-wrapper">
                                <?php
                                $rules = get_option('wfn_settings_rules');
                                if (empty($rules)) {
                                    $rules = [['form_id' => '', 'phone_field_name' => 'phone', 'message' => 'Salam {inputs.names}!

Terima kasih diatas pendaftaran anda.']];
                                }
                                $all_forms = wfn_snippet_get_forms();
                                foreach ($rules as $index => $rule) {
                                    wfn_snippet_render_rule_row($index, $rule, $all_forms);
                                }
                                ?>
                            </div>
                            <button type="button" id="wfn-add-rule-button" class="button button-primary" style="margin-top:20px;">Add New Rule</button>
                            <?php submit_button(); ?>
                        </form>
                    </div>
                </div>
                
                <!-- Wasapbot Tab -->
                <div class="wfn-tab-content <?php echo $current_tab === 'wasapbot' ? 'active' : ''; ?>">
                    <h2>Wasapbot Configuration</h2>
                    
                    <div class="wfn-coming-soon">
                        <span class="dashicons dashicons-admin-generic" style="font-size: 48px; margin-bottom: 20px; color: #ddd;"></span>
                        <h3>Coming Soon</h3>
                        <p>Wasapbot integration will be available in the next update.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/template" id="wfn-rule-template">
            <?php wfn_snippet_render_rule_row('{{index}}', [], wfn_snippet_get_forms()); ?>
        </script>
        
        <div id="wfn-shortcode-popover"></div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            window.wfn_data = <?php echo json_encode(['shortcodes' => wfn_snippet_get_all_shortcodes()]); ?>;

            // API Key Save Logic
            $('#wfn-save-api-key-button').on('click', function() {
                const button = $(this), input = $('#wfn_api_key_field'), notice = $('#wfn-api-key-save-notice'), newKey = input.val();
                if (!newKey || newKey.includes('•')) { input.val(''); return; }
                button.prop('disabled', true); notice.text('Saving...').removeClass('success').show();
                $.post(ajaxurl, { action: 'wfn_snippet_save_key', _ajax_nonce: '<?php echo wp_create_nonce("wfn_save_key"); ?>', api_key: newKey })
                .done(res => { 
                    if(res.success) { 
                        notice.html('✅ Token verified successfully!').addClass('success'); 
                        input.val('••••••••••••••••••••'); 
                        setTimeout(() => location.reload(), 2000);
                    } 
                })
                .always(() => { button.prop('disabled', false); setTimeout(() => notice.fadeOut(), 3000); });
            });

            // Add Rule Logic
            $('body').on('click', '#wfn-add-rule-button', function() {
                const wrapper = $('#wfn-rules-wrapper');
                let newIndex = 0;
                const rows = wrapper.find('.wfn-rule-row');
                if (rows.length > 0) {
                    rows.each(function() {
                        const name = $(this).find('select, input, textarea').first().attr('name');
                        if (name) {
                            const match = name.match(/\[(\d+)\]/);
                            if (match) {
                                const index = parseInt(match[1], 10);
                                if (index >= newIndex) { newIndex = index + 1; }
                            }
                        }
                    });
                }
                const template = $('#wfn-rule-template').html().replace(/{{index}}/g, newIndex);
                wrapper.append(template);
            });

            // Delete Rule Logic
            $('#wfn-rules-wrapper').on('click', '.wfn-delete-rule-button', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this rule?')) {
                    if ($('#wfn-rules-wrapper .wfn-rule-row').length > 1) {
                        $(this).closest('.wfn-rule-row').remove();
                    } else {
                        $(this).closest('.wfn-rule-row').find('input[type="text"], textarea').val('');
                        $(this).closest('.wfn-rule-row').find('select').val('');
                    }
                }
            });

            // Media Toggle Logic
            $('#wfn-rules-wrapper').on('change', '.wfn-media-toggle input[type="checkbox"]', function() {
                const mediaOptions = $(this).closest('.wfn-rule-row').find('.wfn-media-options');
                if ($(this).is(':checked')) {
                    mediaOptions.addClass('active');
                } else {
                    mediaOptions.removeClass('active');
                }
            });

            // Shortcode Popover Logic
            const $popover = $('#wfn-shortcode-popover');
            $('#wfn-rules-wrapper').on('click', '.wfn-add-shortcode-button', function(e) {
                e.preventDefault(); e.stopPropagation();
                if ($popover.is(':visible') && $popover.data('button') === this) { $popover.hide(); return; }

                const button = $(this);
                const formId = button.closest('.wfn-rule-row').find('.wfn-form-selector').val();
                $popover.data('target-textarea', button.data('target-textarea')).data('button', this);
                
                let content = '';
                if (formId && wfn_data.shortcodes[formId] && wfn_data.shortcodes[formId].length > 0) {
                    content = '<ul>' + wfn_data.shortcodes[formId].map(sc => `<li data-shortcode="${sc.code}"><span>${sc.label}</span><code>${sc.code}</code></li>`).join('') + '</ul>';
                } else {
                    content = `<div class="message">${formId ? 'No input fields found.' : 'Please select a form first.'}</div>`;
                }
                $popover.html(content);
                
                const buttonOffset = button.offset();
                $popover.show();
                const popoverHeight = $popover.outerHeight();
                const popoverWidth = $popover.outerWidth();
                $popover.css({
                    top: buttonOffset.top - popoverHeight - 12,
                    left: buttonOffset.left + (button.outerWidth() / 2) - (popoverWidth / 2)
                });
            });

            $popover.on('click', 'li', function(e) {
                e.preventDefault();
                const shortcode = $(this).data('shortcode');
                const textarea = $($(this).closest('#wfn-shortcode-popover').data('target-textarea'))[0];
                let startPos = textarea.selectionStart; let endPos = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, startPos) + shortcode + textarea.value.substring(endPos);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = startPos + shortcode.length;
                $popover.hide();
            });

            $(document).on('click', function(e) { if (!$(e.target).closest('.wfn-add-shortcode-button, #wfn-shortcode-popover').length) $popover.hide(); });
        });
    </script>
    <?php
}

// 4. FUNGSI RENDER RULE ROW (UPDATED WITH MEDIA SUPPORT)
function wfn_snippet_render_rule_row($index, $rule, $all_forms) {
    $form_id = isset($rule['form_id']) ? $rule['form_id'] : '';
    $phone_field = isset($rule['phone_field_name']) ? $rule['phone_field_name'] : 'phone';
    $message = isset($rule['message']) ? $rule['message'] : 'Salam, {inputs.names}!';
    $textarea_id = 'wfn_message_' . esc_attr($index);
    
    // Media settings
    $enable_media = isset($rule['enable_media']) ? $rule['enable_media'] : '';
    $media_type = isset($rule['media_type']) ? $rule['media_type'] : 'image';
    $media_url = isset($rule['media_url']) ? $rule['media_url'] : '';
    //$media_caption = isset($rule['media_caption']) ? $rule['media_caption'] : '';
    ?>
    <div class="wfn-rule-row">
        <div class="wfn-rule-header">
            <h4>Rule #<?php echo is_numeric($index) ? $index + 1 : '{{index}}'; ?></h4>
            <a href="#" class="wfn-delete-rule-button">&times;</a>
        </div>
        <table class="form-table">
            <tr><th><label>Target Form</label></th><td>
                <select class="wfn-form-selector" name="wfn_settings_rules[<?php echo esc_attr($index); ?>][form_id]">
                    <option value="">-- Select --</option>
                    <?php foreach($all_forms as $form) printf('<option value="%s" %s>%s</option>', esc_attr($form->id), selected($form_id, $form->id, false), esc_html($form->title)); ?>
                </select>
            </td></tr>
            <tr><th><label>Phone Field Name</label></th><td>
                <input type="text" class="regular-text" name="wfn_settings_rules[<?php echo esc_attr($index); ?>][phone_field_name]" value="<?php echo esc_attr($phone_field); ?>">
            </td></tr>
            <tr><th><label>Message Template</label></th><td>
                <textarea id="<?php echo esc_attr($textarea_id); ?>" name="wfn_settings_rules[<?php echo esc_attr($index); ?>][message]" rows="4" class="large-text"><?php echo esc_textarea($message); ?></textarea>
                <button type="button" class="button wfn-add-shortcode-button" data-target-textarea="#<?php echo esc_attr($textarea_id); ?>">Add Shortcode</button>
            </td></tr>
        </table>
        
        <!-- Media Section -->
        <div class="wfn-media-section">
            <div class="wfn-media-toggle">
                <label>
                    <input type="checkbox" name="wfn_settings_rules[<?php echo esc_attr($index); ?>][enable_media]" value="1" <?php checked($enable_media, '1'); ?>>
                    <strong>Enable Media Attachment</strong>
                </label>
            </div>
            
            <div class="wfn-media-options <?php echo $enable_media ? 'active' : ''; ?>">
                <div class="wfn-media-type-selector">
                    <label><strong>Media Type:</strong></label><br>
                    <select name="wfn_settings_rules[<?php echo esc_attr($index); ?>][media_type]">
                        <option value="image" <?php selected($media_type, 'image'); ?>>Image (JPG, PNG, GIF)</option>
                        <option value="video" <?php selected($media_type, 'video'); ?>>Video (MP4, 3GP, MOV)</option>
                        <option value="document" <?php selected($media_type, 'document'); ?>>Document (PDF, DOC, XLS, etc.)</option>
                        <option value="audio" <?php selected($media_type, 'audio'); ?>>Audio (MP3, WAV, OGG)</option>
                    </select>
                </div>
                
                <div class="wfn-media-url-input">
                    <label><strong>Media URL:</strong></label><br>
                    <input type="url" class="large-text" name="wfn_settings_rules[<?php echo esc_attr($index); ?>][media_url]" value="<?php echo esc_attr($media_url); ?>" placeholder="https://example.com/media.jpg">
                    <p class="description">Enter the full URL to your media file. You can use shortcodes like {inputs.file_upload} if your form has file upload fields.</p>
                </div>
                                                
                <div class="wfn-media-info">
                    <strong>Supported formats:</strong><br>
                    • Images: JPG, PNG, GIF (max 5MB)<br>
                    • Videos: MP4, 3GP, MOV (max 16MB)<br>
                    • Documents: PDF, DOC, XLS, PPT, etc. (max 100MB)<br>
                    • Audio: MP3, WAV, OGG (max 16MB)
                </div>
            </div>
        </div>
    </div>
    <?php
}

// 5. UPDATED SANITIZE FUNCTION
function wfn_snippet_sanitize_rules($rules) {
    if (empty($rules)) return [];
    return array_values(array_filter(array_map(function($rule) {
        if (empty($rule['form_id']) || empty($rule['phone_field_name'])) return null;
        
        $sanitized = [
            'form_id' => intval($rule['form_id']),
            'phone_field_name' => sanitize_text_field($rule['phone_field_name']),
            'message' => sanitize_textarea_field($rule['message']),
            'enable_media' => isset($rule['enable_media']) ? '1' : '',
            'media_type' => sanitize_text_field($rule['media_type'] ?? 'image'),
            'media_url' => esc_url_raw($rule['media_url'] ?? ''),
            'media_caption' => sanitize_textarea_field($rule['media_caption'] ?? '')
        ];
        
        return $sanitized;
    }, (array) $rules)));
}

// 6. HELPER FUNCTIONS
function wfn_snippet_get_forms() { 
    return class_exists('\FluentForm\App\Models\Form') ? \FluentForm\App\Models\Form::all(['id', 'title']) : []; 
}

function wfn_snippet_get_all_shortcodes() {
    if (!class_exists('\FluentForm\App\Models\Form')) return [];
    $all_shortcodes = []; $forms = \FluentForm\App\Models\Form::all();
    foreach ($forms as $form) {
        if (empty($form->form_fields)) continue;
        $fields_data = json_decode($form->form_fields, true);
        $fields = \FluentForm\Framework\Helpers\ArrayHelper::get($fields_data, 'fields');
        if (is_array($fields)) {
            $all_shortcodes[$form->id] = [];
            foreach ($fields as $field) {
                $name = \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'attributes.name');
                if ($name) $all_shortcodes[$form->id][] = ['label' => \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'settings.label', 'Untitled'), 'code' => '{inputs.' . $name . '}'];
                $sub_fields = \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'fields');
                if(is_array($sub_fields)) foreach($sub_fields as $sub_field) {
                    $sub_name = \FluentForm\Framework\Helpers\ArrayHelper::get($sub_field, 'attributes.name');
                    if($sub_name) $all_shortcodes[$form->id][] = ['label' => \FluentForm\Framework\Helpers\ArrayHelper::get($sub_field, 'settings.label', 'Sub-field'), 'code' => '{inputs.' . $sub_name . '}'];
                }
            }
        }
    } 
    return $all_shortcodes;
}


// 7. AJAX & CRON LOGIC - CORRECTED API VERIFICATION
add_action('wp_ajax_wfn_snippet_save_key', function() {
    check_ajax_referer('wfn_save_key');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $api_key = sanitize_text_field($_POST['api_key']);
    
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API Key is required']);
    }
    
    // Test API key with device status endpoint (you need to check this endpoint from Wasapbom docs)
    $response = wp_remote_get('https://panel.wasapbom.com/api/device/status', [
        'headers' => [
            'X-API-KEY' => $api_key,
            'Content-Type' => 'application/json'
        ],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Failed to verify API key: ' . $response->get_error_message()]);
    }
    
    $body = wp_remote_retrieve_body($response);
    $response_code = wp_remote_retrieve_response_code($response);
    
    // Log the response for debugging
    error_log('API Key verification response code: ' . $response_code);
    error_log('API Key verification response body: ' . $body);
    
    if ($response_code === 200) {
        update_option('wfn_api_key_wasapbom', $api_key);
        wp_send_json_success(['message' => 'API Key verified and saved successfully']);
    } else {
        wp_send_json_error(['message' => 'Invalid API Key or service unavailable. Response: ' . $body]);
    }
});

// 8. MAIN NOTIFICATION HANDLER
add_action('fluentform/submission_inserted', 'wfn_snippet_send_notification', 10, 3);
function wfn_snippet_send_notification($entryId, $formData, $form) {
    $rules = get_option('wfn_settings_rules', []);
    $api_key = get_option('wfn_api_key_wasapbom', '');
    
    if (empty($api_key) || empty($rules)) return;
    
    foreach ($rules as $rule) {
        if ($rule['form_id'] != $form->id) continue;
        
        $phone_field = $rule['phone_field_name'];
        $phone_number = isset($formData[$phone_field]) ? $formData[$phone_field] : '';
        
        if (empty($phone_number)) continue;
        
        // Clean and format phone number
        $phone_number = wfn_snippet_format_phone($phone_number);
        
        // Process message template
        $message = wfn_snippet_process_template($rule['message'], $formData);
        
        // Prepare API payload
        $payload = [
            'number' => $phone_number,
            'message' => $message
        ];
        
        // Handle media attachment if enabled
        if (!empty($rule['enable_media']) && !empty($rule['media_url'])) {
            $media_url = wfn_snippet_process_template($rule['media_url'], $formData);
            //$media_caption = !empty($rule['media_caption']) ? wfn_snippet_process_template($rule['media_caption'], $formData) : '';
            
            // Validate media URL
            if (filter_var($media_url, FILTER_VALIDATE_URL)) {
                $payload['media_url'] = $media_url;
                $payload['media_type'] = $rule['media_type'];
                
                if (!empty($media_caption)) {
                    $payload['caption'] = $media_caption;
                }
            }
        }
        
        // Send notification
        wfn_snippet_send_whatsapp_message($payload, $api_key);
    }
}

// 9. CORRECTED SEND WHATSAPP MESSAGE FUNCTION
function wfn_snippet_send_whatsapp_message($payload, $api_key) {
    // Determine the correct endpoint based on whether media is included
    if (!empty($payload['media_url'])) {
        // For media message (URL)
        $endpoint = 'https://panel.wasapbom.com/api/whatsapp/send/media';
        
        // Prepare payload for media message
        $api_payload = [
            'number' => $payload['number'],
            'mediaMessage' => [
                'mediatype' => $payload['media_type'],
                'media' => $payload['media_url'],
                'caption' => $payload['message']
            ]
        ];
        
        // Add fileName for document type
        if ($payload['media_type'] === 'document') {
            // Extract filename from URL or use provided filename
            if (!empty($payload['filename'])) {
                $api_payload['mediaMessage']['fileName'] = $payload['filename'];
            } else {
                // Extract filename from URL
                $parsed_url = parse_url($payload['media_url']);
                $filename = basename($parsed_url['path']);
                $api_payload['mediaMessage']['fileName'] = $filename;
            }
        }
        
        // Override caption if specifically provided
        if (!empty($payload['caption'])) {
            $api_payload['mediaMessage']['caption'] = $payload['caption'];
        }
        
    } else {
        // For text message
        $endpoint = 'https://panel.wasapbom.com/api/whatsapp/send/text';
        
        // Prepare payload for text message
        $api_payload = [
            'number' => $payload['number'],
            'textMessage' => [
                'text' => $payload['message']
            ]
        ];
    }
    
    // Log the endpoint and payload being used
    error_log('Using WhatsApp API endpoint: ' . $endpoint);
    error_log('WhatsApp API payload: ' . json_encode($api_payload));
    
    $response = wp_remote_post($endpoint, [
        'headers' => [
            'X-API-KEY' => $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($api_payload),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        error_log('WhatsApp Notification Error: ' . $response->get_error_message());
        error_log('Error Code: ' . $response->get_error_code());
        error_log('Endpoint used: ' . $endpoint);
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('WhatsApp API Response Code: ' . $response_code);
    error_log('WhatsApp API Response Body: ' . $body);
    
    if ($response_code === 200) {
        $data = json_decode($body, true);
        if (isset($data['success']) && $data['success'] === true) {
            return true;
        }
        if (isset($data['message']) && strpos($data['message'], 'sent successfully') !== false) {
            return true;
        }
    }
    
    error_log('WhatsApp Notification Failed: ' . $body);
    return false;
}


// 10. TEMPLATE PROCESSING FUNCTION
function wfn_snippet_process_template($template, $formData) {
    $processed = $template;
    
    // Process {inputs.field_name} shortcodes
    preg_match_all('/\{inputs\.([^}]+)\}/', $template, $matches);
    
    foreach ($matches[1] as $field_name) {
        $field_value = '';
        
        if (isset($formData[$field_name])) {
            $value = $formData[$field_name];
            
            // Handle different field types
            if (is_array($value)) {
                // Handle checkbox arrays, select multiple, etc.
                $field_value = implode(', ', array_filter($value));
            } elseif (is_string($value)) {
                $field_value = $value;
            } else {
                $field_value = (string) $value;
            }
        }
        
        $processed = str_replace('{inputs.' . $field_name . '}', $field_value, $processed);
    }
    
    // Process other shortcodes
    $processed = str_replace('{site_name}', get_bloginfo('name'), $processed);
    $processed = str_replace('{site_url}', get_site_url(), $processed);
    $processed = str_replace('{current_date}', date('Y-m-d'), $processed);
    $processed = str_replace('{current_time}', date('H:i:s'), $processed);
    
    return $processed;
}

// 11. PHONE NUMBER FORMATTING
function wfn_snippet_format_phone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Handle Malaysian numbers
    if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
        // Convert 0123456789 to 60123456789
        $phone = '60' . substr($phone, 1);
    } elseif (strlen($phone) == 9) {
        // Convert 123456789 to 60123456789
        $phone = '60' . $phone;
    } elseif (strlen($phone) >= 10 && substr($phone, 0, 2) != '60') {
        // If it's international but doesn't start with 60, assume it's already formatted
        // You might want to add more country-specific logic here
    }
    
    return $phone;
}

// 12. ACTIVATION & DEACTIVATION HOOKS
register_activation_hook(__FILE__, 'wfn_snippet_activate');
function wfn_snippet_activate() {
    // Set default settings
    if (!get_option('wfn_settings_rules')) {
        update_option('wfn_settings_rules', [
            [
                'form_id' => '',
                'phone_field_name' => 'phone',
                'message' => 'Thank you for your submission!',
                'enable_media' => '',
                'media_type' => 'image',
                'media_url' => '',
                'media_caption' => ''
            ]
        ]);
    }
}

register_deactivation_hook(__FILE__, 'wfn_snippet_deactivate');
function wfn_snippet_deactivate() {
    // Clean up if needed
    // Note: We don't delete settings on deactivation in case user wants to reactivate
}

// 13. UNINSTALL HOOK
register_uninstall_hook(__FILE__, 'wfn_snippet_uninstall');
function wfn_snippet_uninstall() {
    // Clean up all plugin data
    delete_option('wfn_settings_rules');
    delete_option('wfn_api_key_wasapbom');
}

// 14. ADMIN NOTICES
add_action('admin_notices', 'wfn_snippet_admin_notices');
function wfn_snippet_admin_notices() {
    if (get_current_screen()->id !== 'fluent-forms_page_wfn_settings_snippet') return;
    
    $api_key = get_option('wfn_api_key_wasapbom', '');
    $rules = get_option('wfn_settings_rules', []);
    
    if (empty($api_key)) {
        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>WhatsApp Notifications:</strong> Please configure your Wasapbom API key to enable WhatsApp notifications.</p>
        </div>';
    }
    
    if (empty($rules) || (count($rules) == 1 && empty($rules[0]['form_id']))) {
        echo '<div class="notice notice-info is-dismissible">
            <p><strong>WhatsApp Notifications:</strong> Please configure at least one notification rule to start sending WhatsApp messages.</p>
        </div>';
    }
}

// 15. SHORTCODE SUPPORT (OPTIONAL)
add_shortcode('wfn_status', 'wfn_snippet_status_shortcode');
function wfn_snippet_status_shortcode($atts) {
    $api_key = get_option('wfn_api_key_wasapbom', '');
    $rules = get_option('wfn_settings_rules', []);
    
    $status = !empty($api_key) ? 'Connected' : 'Not Connected';
    $rules_count = count(array_filter($rules, function($rule) { return !empty($rule['form_id']); }));
    
    return sprintf(
        '<div class="wfn-status">API Status: %s | Active Rules: %d</div>',
        $status,
        $rules_count
    );
}

// 16. DEBUG LOGGING (FOR DEVELOPMENT)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wfn_debug_log', 'wfn_snippet_debug_log');
    function wfn_snippet_debug_log($message) {
        error_log('[WFN Debug] ' . $message);
    }
}

// 17. PLUGIN META LINKS
add_filter('plugin_row_meta', 'wfn_snippet_plugin_meta', 10, 2);
function wfn_snippet_plugin_meta($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $links[] = '<a href="admin.php?page=wfn_settings_snippet">Settings</a>';
        $links[] = '<a href="https://webbku.com" target="_blank">Support</a>';
    }
    return $links;
}

// 18. SECURITY ENHANCEMENTS
add_action('init', 'wfn_snippet_security_headers');
function wfn_snippet_security_headers() {
    // Add security headers for admin pages
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'wfn_settings_snippet') {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
}

// 19. CRON JOB FOR FAILED MESSAGES (OPTIONAL)
if (!wp_next_scheduled('wfn_retry_failed_messages')) {
    wp_schedule_event(time(), 'hourly', 'wfn_retry_failed_messages');
}

add_action('wfn_retry_failed_messages', 'wfn_snippet_retry_failed_messages');
function wfn_snippet_retry_failed_messages() {
    // This function can be implemented to retry failed messages
    // For now, it's a placeholder for future enhancement
    
    // Get failed messages from database (you'd need to implement this)
    // Retry sending them
    // Clean up old failed messages
}

// 20. FINAL SECURITY CHECK
if (!function_exists('add_action')) {
    die('Direct access not allowed');
}




?>