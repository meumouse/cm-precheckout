<?php

namespace MeuMouse\Cm_Precheckout\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Options Library Manager for Pre-checkout Steps
 * 
 * @since 1.1.0
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Options_Library {
    
    /**
     * Default options configuration
     * 
     * @since 1.1.0
     * @var array
     */
    private $default_options = array(
        'material_selection' => array(
            'key' => 'material_selection',
            'name' => 'Seleção de Material',
            'icon' => 'dashicons-admin-appearance',
            'enabled' => true,
            'config' => array(
                'required' => true,
                'display_name' => 'Material',
                'additional_message' => 'Selecione o material do seu anel',
                'link_variation' => true,
            ),
            'fields' => array(
                'materials' => array(
                    'type' => 'checkbox_group',
                    'label' => 'Materiais disponíveis',
                ),
            ),
        ),
        'size_selection' => array(
            'key' => 'size_selection',
            'name' => 'Seleção de Tamanho',
            'icon' => 'dashicons-admin-generic',
            'enabled' => true,
            'config' => array(
                'required' => true,
                'display_name' => 'Tamanho',
                'additional_message' => 'Selecione o tamanho do anel',
                'link_variation' => false,
            ),
            'fields' => array(
                'enable_size_selection' => array(
                    'type' => 'checkbox',
                    'label' => 'Ativar seleção de tamanhos',
                ),
                'size_selectors' => array(
                    'type' => 'select',
                    'label' => 'Número de seletores',
                    'options' => array(1, 2, 3, 4),
                ),
            ),
        ),
        'personalization' => array(
            'key' => 'personalization',
            'name' => 'Personalização',
            'icon' => 'dashicons-edit',
            'enabled' => true,
            'config' => array(
                'required' => false,
                'display_name' => 'Personalização',
                'additional_message' => 'Personalize seu anel',
                'link_variation' => false,
            ),
            'fields' => array(
                'enable_name_engraving' => array(
                    'type' => 'checkbox',
                    'label' => 'Ativar gravação de nomes',
                ),
                'name_fields' => array(
                    'type' => 'select',
                    'label' => 'Número de campos',
                    'options' => array(1, 2, 3, 4),
                ),
                'enable_course_change' => array(
                    'type' => 'checkbox',
                    'label' => 'Ativar alteração de curso',
                ),
                'enable_stone_sample' => array(
                    'type' => 'checkbox',
                    'label' => 'Ativar amostra de pedras',
                ),
                'enable_emblems_sample' => array(
                    'type' => 'checkbox',
                    'label' => 'Ativar amostra de emblemas',
                ),
            ),
        ),
        'file_upload' => array(
            'key' => 'file_upload',
            'name' => 'Upload de Arquivo',
            'icon' => 'dashicons-media-document',
            'enabled' => false,
            'config' => array(
                'required' => false,
                'display_name' => 'Arquivo',
                'additional_message' => 'Envie um arquivo para personalização',
                'allowed_types' => array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'),
                'max_size' => 5, // MB
            ),
            'fields' => array(
                'allowed_types' => array(
                    'type' => 'text',
                    'label' => 'Tipos de arquivo permitidos',
                ),
                'max_size' => array(
                    'type' => 'number',
                    'label' => 'Tamanho máximo (MB)',
                ),
            ),
        ),
        'summary' => array(
            'key' => 'summary',
            'name' => 'Resumo',
            'icon' => 'dashicons-list-view',
            'enabled' => true,
            'config' => array(
                'required' => false,
                'display_name' => 'Resumo',
                'additional_message' => 'Revise seu pedido',
                'link_variation' => false,
            ),
            'fields' => array(),
        ),
    );

    /**
     * Constructor
     * 
     * @since 1.1.0
     * @version 1.1.0
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_library_menu'));
        add_action('wp_ajax_cm_precheckout_save_option_config', array($this, 'save_option_config'));
        add_action('wp_ajax_cm_precheckout_reorder_steps', array($this, 'reorder_steps'));
        add_action('cm_precheckout_activated', array($this, 'set_default_options'));
    }

    /**
     * Add library menu
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function add_library_menu() {
        add_submenu_page(
            'cm-precheckout',
            esc_html__('Biblioteca de Opções', 'cm-precheckout'),
            esc_html__('Biblioteca de Opções', 'cm-precheckout'),
            'manage_options',
            'cm-precheckout-library',
            array($this, 'library_page')
        );
    }

    /**
     * Set default options on activation
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function set_default_options() {
        $options = get_option('cm_precheckout_library_options', array());
        
        if (empty($options)) {
            update_option('cm_precheckout_library_options', $this->default_options);
        }
    }

    /**
     * Get all library options
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return array
     */
    public function get_library_options() {
        $options = get_option('cm_precheckout_library_options', array());
        
        // Merge with defaults if empty
        if (empty($options)) {
            $options = $this->default_options;
            update_option('cm_precheckout_library_options', $options);
        }
        
        return $options;
    }

    /**
     * Get enabled options
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return array
     */
    public function get_enabled_options() {
        $options = $this->get_library_options();
        return array_filter($options, function($option) {
            return $option['enabled'];
        });
    }

    /**
     * Get option by key
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param string $key
     * @return array|null
     */
    public function get_option_by_key($key) {
        $options = $this->get_library_options();
        return isset($options[$key]) ? $options[$key] : null;
    }

    /**
     * Save option configuration
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function save_option_config() {
        // Verify nonce and permissions
        if (!check_ajax_referer('cm_precheckout_admin_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Permissão negada.', 'cm-precheckout')
            ));
        }

        $option_key = sanitize_text_field($_POST['option_key']);
        $config = array_map('sanitize_text_field', $_POST['config']);
        
        $options = $this->get_library_options();
        
        if (isset($options[$option_key])) {
            $options[$option_key]['config'] = wp_parse_args($config, $options[$option_key]['config']);
            update_option('cm_precheckout_library_options', $options);
            
            wp_send_json_success(array(
                'message' => esc_html__('Configuração salva com sucesso!', 'cm-precheckout')
            ));
        } else {
            wp_send_json_error(array(
                'message' => esc_html__('Opção não encontrada.', 'cm-precheckout')
            ));
        }
    }

    /**
     * Reorder steps
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function reorder_steps() {
        // Verify nonce and permissions
        if (!check_ajax_referer('cm_precheckout_admin_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => esc_html__('Permissão negada.', 'cm-precheckout')
            ));
        }

        $order = array_map('sanitize_text_field', $_POST['order']);
        $options = $this->get_library_options();
        $reordered = array();

        foreach ($order as $key) {
            if (isset($options[$key])) {
                $reordered[$key] = $options[$key];
            }
        }

        // Add any missing options
        foreach ($options as $key => $option) {
            if (!isset($reordered[$key])) {
                $reordered[$key] = $option;
            }
        }

        update_option('cm_precheckout_library_options', $reordered);
        
        wp_send_json_success(array(
            'message' => esc_html__('Ordem atualizada com sucesso!', 'cm-precheckout')
        ));
    }

    /**
     * Library page
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function library_page() {
        $options = $this->get_library_options();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Biblioteca de Opções de Pré-Checkout', 'cm-precheckout'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php esc_html_e('Arraste e solte as etapas para reordenar. Clique em "Editar" para configurar cada opção.', 'cm-precheckout'); ?></p>
            </div>
            
            <div id="options-library" class="cm-precheckout-library">
                <?php foreach ($options as $key => $option): ?>
                    <div class="option-item" data-key="<?php echo esc_attr($key); ?>">
                        <div class="option-header">
                            <span class="dashicons <?php echo esc_attr($option['icon']); ?>"></span>
                            <h3><?php echo esc_html($option['name']); ?></h3>
                            <div class="option-actions">
                                <label class="switch">
                                    <input type="checkbox" class="option-toggle" 
                                           data-key="<?php echo esc_attr($key); ?>"
                                           <?php checked($option['enabled'], true); ?>>
                                    <span class="slider"></span>
                                </label>
                                <button type="button" class="button button-small edit-option" 
                                        data-key="<?php echo esc_attr($key); ?>">
                                    <?php esc_html_e('Editar', 'cm-precheckout'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <?php if (!empty($option['config'])): ?>
                            <div class="option-config">
                                <p><strong><?php esc_html_e('Configurações:', 'cm-precheckout'); ?></strong></p>
                                <ul>
                                    <li><?php esc_html_e('Obrigatório:', 'cm-precheckout'); ?> 
                                        <?php echo $option['config']['required'] ? esc_html__('Sim', 'cm-precheckout') : esc_html__('Não', 'cm-precheckout'); ?>
                                    </li>
                                    <li><?php esc_html_e('Nome de exibição:', 'cm-precheckout'); ?> 
                                        <?php echo esc_html($option['config']['display_name']); ?>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Edit Option Modal -->
            <div id="edit-option-modal" class="cm-modal" style="display: none;">
                <div class="cm-modal-overlay"></div>
                <div class="cm-modal-content">
                    <div class="cm-modal-header">
                        <h3><?php esc_html_e('Editar Configuração da Opção', 'cm-precheckout'); ?></h3>
                        <button type="button" class="cm-modal-close">&times;</button>
                    </div>
                    <div class="cm-modal-body">
                        <form id="option-config-form">
                            <input type="hidden" id="option_key" name="option_key">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="required"><?php esc_html_e('Obrigatório', 'cm-precheckout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="checkbox" id="required" name="config[required]" value="1">
                                        <p class="description"><?php esc_html_e('Torna esta etapa obrigatória', 'cm-precheckout'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="display_name"><?php esc_html_e('Nome de exibição', 'cm-precheckout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="display_name" name="config[display_name]" 
                                               class="regular-text" value="">
                                        <p class="description"><?php esc_html_e('Nome que será exibido no frontend', 'cm-precheckout'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="additional_message"><?php esc_html_e('Mensagem adicional', 'cm-precheckout'); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="additional_message" name="config[additional_message]" 
                                                  class="large-text" rows="3"></textarea>
                                        <p class="description"><?php esc_html_e('Mensagem que aparecerá como tooltip no frontend', 'cm-precheckout'); ?></p>
                                    </td>
                                </tr>
                                <tr id="link_variation_row" style="display: none;">
                                    <th scope="row">
                                        <label for="link_variation"><?php esc_html_e('Vincular a variação', 'cm-precheckout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="checkbox" id="link_variation" name="config[link_variation]" value="1">
                                        <p class="description"><?php esc_html_e('Permite vincular esta opção a uma variação do produto', 'cm-precheckout'); ?></p>
                                    </td>
                                </tr>
                                <tr id="allowed_types_row" style="display: none;">
                                    <th scope="row">
                                        <label for="allowed_types"><?php esc_html_e('Tipos de arquivo permitidos', 'cm-precheckout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="allowed_types" name="config[allowed_types]" 
                                               class="regular-text" value="jpg,jpeg,png,pdf,doc,docx">
                                        <p class="description"><?php esc_html_e('Separar por vírgula', 'cm-precheckout'); ?></p>
                                    </td>
                                </tr>
                                <tr id="max_size_row" style="display: none;">
                                    <th scope="row">
                                        <label for="max_size"><?php esc_html_e('Tamanho máximo (MB)', 'cm-precheckout'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="max_size" name="config[max_size]" 
                                               class="small-text" value="5" min="1" max="50">
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div class="cm-modal-footer">
                        <button type="button" class="button button-secondary cm-modal-close">
                            <?php esc_html_e('Cancelar', 'cm-precheckout'); ?>
                        </button>
                        <button type="button" class="button button-primary save-option-config">
                            <?php esc_html_e('Salvar', 'cm-precheckout'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .cm-precheckout-library {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .option-item {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 15px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .option-header {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .option-header .dashicons {
                font-size: 24px;
                margin-right: 10px;
                color: #0073aa;
            }
            .option-header h3 {
                margin: 0;
                flex: 1;
            }
            .option-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }
            .slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            input:checked + .slider {
                background-color: #0073aa;
            }
            input:checked + .slider:before {
                transform: translateX(26px);
            }
            .option-config {
                background: #f9f9f9;
                padding: 10px;
                border-radius: 4px;
                font-size: 13px;
            }
            .option-config ul {
                margin: 5px 0;
                padding-left: 20px;
            }
            .option-config li {
                margin-bottom: 3px;
            }
            /* Modal Styles */
            .cm-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
            }
            .cm-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
            }
            .cm-modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #fff;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                overflow-y: auto;
                border-radius: 4px;
                box-shadow: 0 2px 30px rgba(0,0,0,0.3);
            }
            .cm-modal-header {
                padding: 15px 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .cm-modal-header h3 {
                margin: 0;
            }
            .cm-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }
            .cm-modal-body {
                padding: 20px;
            }
            .cm-modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #ddd;
                text-align: right;
                background: #f9f9f9;
            }
            .cm-modal-footer .button {
                margin-left: 10px;
            }
        </style>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Make library sortable
                $('#options-library').sortable({
                    handle: '.option-header',
                    update: function(event, ui) {
                        var order = [];
                        $('#options-library .option-item').each(function() {
                            order.push($(this).data('key'));
                        });
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cm_precheckout_reorder_steps',
                                nonce: '<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>',
                                order: order
                            },
                            success: function(response) {
                                if (response.success) {
                                    showNotice('success', response.data.message);
                                }
                            }
                        });
                    }
                });
                
                // Toggle option enabled/disabled
                $('.option-toggle').change(function() {
                    var $toggle = $(this);
                    var key = $toggle.data('key');
                    var enabled = $toggle.is(':checked');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cm_precheckout_toggle_option',
                            nonce: '<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>',
                            key: key,
                            enabled: enabled
                        },
                        success: function(response) {
                            if (!response.success) {
                                $toggle.prop('checked', !enabled);
                                showNotice('error', response.data.message);
                            }
                        }
                    });
                });
                
                // Edit option modal
                $('.edit-option').click(function() {
                    var key = $(this).data('key');
                    var option = <?php echo json_encode($options); ?>[key];
                    
                    $('#option_key').val(key);
                    
                    // Fill form with current values
                    if (option.config) {
                        $('#required').prop('checked', option.config.required == true || option.config.required == '1');
                        $('#display_name').val(option.config.display_name || '');
                        $('#additional_message').val(option.config.additional_message || '');
                        
                        if (option.key === 'material_selection') {
                            $('#link_variation_row').show();
                            $('#link_variation').prop('checked', option.config.link_variation == true || option.config.link_variation == '1');
                        } else {
                            $('#link_variation_row').hide();
                        }
                        
                        if (option.key === 'file_upload') {
                            $('#allowed_types_row').show();
                            $('#max_size_row').show();
                            $('#allowed_types').val(option.config.allowed_types || 'jpg,jpeg,png,pdf,doc,docx');
                            $('#max_size').val(option.config.max_size || 5);
                        } else {
                            $('#allowed_types_row').hide();
                            $('#max_size_row').hide();
                        }
                    }
                    
                    $('#edit-option-modal').show();
                });
                
                // Close modal
                $('.cm-modal-close, .cm-modal-overlay').click(function() {
                    $('#edit-option-modal').hide();
                });
                
                // Save option config
                $('.save-option-config').click(function() {
                    var formData = $('#option-config-form').serialize();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData + '&action=cm_precheckout_save_option_config&nonce=<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>',
                        success: function(response) {
                            if (response.success) {
                                showNotice('success', response.data.message);
                                $('#edit-option-modal').hide();
                                location.reload();
                            } else {
                                showNotice('error', response.data.message);
                            }
                        }
                    });
                });
                
                // Prevent modal close when clicking inside
                $('.cm-modal-content').click(function(e) {
                    e.stopPropagation();
                });
                
                function showNotice(type, message) {
                    var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
                    var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
                    
                    $('.wrap h1').after(notice);
                    
                    setTimeout(function() {
                        notice.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
                
                // Add AJAX handler for toggle option (need to add to Ajax.php)
                $.ajaxSetup({
                    data: {
                        'cm_precheckout_toggle_option': function() {
                            return {
                                action: 'cm_precheckout_toggle_option',
                                nonce: '<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>'
                            };
                        }
                    }
                });
            });
        </script>
        <?php
    }
}