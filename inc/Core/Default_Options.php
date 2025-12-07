<?php

namespace MeuMouse\Cm_Precheckout\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Default options for Courses and Stones
 * 
 * @since 1.1.0
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Default_Options {
    
    /**
     * Default courses
     * 
     * @since 1.1.0
     * @var array
     */
    private $default_courses = array(
        array(
            'name' => 'Engenharia Civil',
            'stone_id' => 0,
            'emblem_1' => 0,
            'emblem_2' => 0,
        ),
        array(
            'name' => 'Medicina',
            'stone_id' => 1,
            'emblem_1' => 0,
            'emblem_2' => 0,
        ),
        array(
            'name' => 'Direito',
            'stone_id' => 2,
            'emblem_1' => 0,
            'emblem_2' => 0,
        ),
        array(
            'name' => 'Arquitetura',
            'stone_id' => 0,
            'emblem_1' => 0,
            'emblem_2' => 0,
        ),
        array(
            'name' => 'Administração',
            'stone_id' => 1,
            'emblem_1' => 0,
            'emblem_2' => 0,
        ),
    );
    
    /**
     * Default stones
     * 
     * @since 1.1.0
     * @var array
     */
    private $default_stones = array(
        array(
            'name' => 'Rubi',
            'icon_url' => '',
            'color' => '#e0115f',
        ),
        array(
            'name' => 'Safira',
            'icon_url' => '',
            'color' => '#0f52ba',
        ),
        array(
            'name' => 'Esmeralda',
            'icon_url' => '',
            'color' => '#50c878',
        ),
        array(
            'name' => 'Diamante',
            'icon_url' => '',
            'color' => '#b9f2ff',
        ),
        array(
            'name' => 'Ametista',
            'icon_url' => '',
            'color' => '#9966cc',
        ),
    );
    
    /**
     * Constructor
     * 
     * @since 1.1.0
     * @version 1.1.0
     */
    public function __construct() {
        add_action('cm_precheckout_activated', array($this, 'set_defaults'));
        add_action('admin_init', array($this, 'add_default_options_button'));
    }
    
    /**
     * Set default options on activation
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function set_defaults() {
        $options = get_option('cm_precheckout_options', array());
        
        // Set default courses if empty
        if (empty($options['courses'])) {
            $options['courses'] = $this->default_courses;
        }
        
        // Set default stones if empty
        if (empty($options['stones'])) {
            $options['stones'] = $this->default_stones;
        }
        
        update_option('cm_precheckout_options', $options);
    }
    
    /**
     * Add default options button to admin pages
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function add_default_options_button() {
        global $pagenow;
        
        if ($pagenow === 'admin.php' && isset($_GET['page'])) {
            $page = $_GET['page'];
            
            if (in_array($page, array('cm-precheckout-courses', 'cm-precheckout-stones'))) {
                add_action('admin_notices', array($this, 'render_default_options_button'));
            }
        }
    }
    
    /**
     * Render default options button
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @return void
     */
    public function render_default_options_button() {
        $page = $_GET['page'];
        $type = $page === 'cm-precheckout-courses' ? 'courses' : 'stones'; ?>

        <div class="notice notice-info">
            <p>
                <?php 
                echo sprintf(
                    esc_html__('Deseja usar %s padrão?', 'cm-precheckout'),
                    $type === 'courses' ? esc_html__('cursos', 'cm-precheckout') : esc_html__('pedras', 'cm-precheckout')
                );
                ?>
                <a href="#" class="button button-secondary set-default-options" 
                   data-type="<?php echo esc_attr($type); ?>" 
                   style="margin-left: 10px;">
                    <?php esc_html_e('Aplicar Padrões', 'cm-precheckout'); ?>
                </a>
            </p>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.set-default-options').click(function(e) {
                    e.preventDefault();
                    
                    var type = $(this).data('type');
                    var message = type === 'courses' ? 
                        '<?php esc_html_e("Isso substituirá todos os cursos existentes. Continuar?", "cm-precheckout"); ?>' :
                        '<?php esc_html_e("Isso substituirá todas as pedras existentes. Continuar?", "cm-precheckout"); ?>';
                    
                    if (confirm(message)) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cm_precheckout_set_default_options',
                                nonce: '<?php echo wp_create_nonce("cm_precheckout_admin_nonce"); ?>',
                                type: type
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert(response.data.message);
                                    location.reload();
                                } else {
                                    alert(response.data.message);
                                }
                            }
                        });
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Get default options by type
     * 
     * @since 1.1.0
     * @version 1.1.0
     * @param string $type
     * @return array
     */
    public function get_defaults($type) {
        return $type === 'courses' ? $this->default_courses : $this->default_stones;
    }
}