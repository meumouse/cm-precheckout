<?php

namespace MeuMouse\Cm_Precheckout\Admin;

/**
 * Main Admin Class
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Admin {

    /**
     * Constructor
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {
        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }


    /**
     * Register plugin settings
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function register_settings() {
        register_setting(
            'cm_precheckout_settings',
            'cm_precheckout_options',
            array( $this, 'sanitize_options' )
        );
    }


    /**
     * Sanitize options
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param array $input
     * @return array
     */
    public function sanitize_options( $input ) {
        $sanitized = array();
        
        // Sanitize courses
        if ( isset( $input['courses'] ) && is_array( $input['courses'] ) ) {
            foreach ( $input['courses'] as $key => $course ) {
                $sanitized['courses'][ $key ] = array(
                    'name' => sanitize_text_field( $course['name'] ),
                    'stone_id' => absint( $course['stone_id'] ),
                    'emblem_1' => absint( $course['emblem_1'] ),
                    'emblem_2' => absint( $course['emblem_2'] )
                );
            }
        }

        // Sanitize stones
        if ( isset( $input['stones'] ) && is_array( $input['stones'] ) ) {
            foreach ( $input['stones'] as $key => $stone ) {
                $sanitized['stones'][ $key ] = array(
                    'name' => sanitize_text_field( $stone['name'] ),
                    'icon_url' => esc_url_raw( $stone['icon_url'] )
                );
            }
        }

        // Sanitize ring sizes
        if ( isset( $input['ring_sizes'] ) ) {
            $sanitized['ring_sizes'] = array_map( 'absint', (array) $input['ring_sizes'] );
        }

        return $sanitized;
    }


    /**
     * Add admin menu
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            esc_html__( 'CM Pré Checkout', 'cm-precheckout' ),
            esc_html__( 'CM Pré Checkout', 'cm-precheckout' ),
            'manage_options',
            'cm-precheckout',
            array( $this, 'main_page' ),
            'dashicons-cart',
            56
        );

        // Submenu: Cursos
        add_submenu_page(
            'cm-precheckout',
            esc_html__( 'Cursos', 'cm-precheckout' ),
            esc_html__( 'Cursos', 'cm-precheckout' ),
            'manage_options',
            'cm-precheckout-courses',
            array( $this, 'courses_page' )
        );

        // Submenu: Pedras
        add_submenu_page(
            'cm-precheckout',
            esc_html__( 'Pedras', 'cm-precheckout' ),
            esc_html__( 'Pedras', 'cm-precheckout' ),
            'manage_options',
            'cm-precheckout-stones',
            array( $this, 'stones_page' )
        );

        // Remove duplicate submenu
        remove_submenu_page( 'cm-precheckout', 'cm-precheckout' );
    }


    /**
     * Main page (redirect to courses)
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function main_page() {
        // Redirect to courses page
        wp_redirect( admin_url( 'admin.php?page=cm-precheckout-courses' ) );
        exit;
    }


    /**
     * Get stones for select dropdown
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    private function get_stones_for_select() {
        $options = get_option( 'cm_precheckout_options', array() );
        $stones = isset( $options['stones'] ) ? $options['stones'] : array();
        
        $stones_list = array(
            '' => __( 'Selecione uma pedra', 'cm-precheckout' )
        );
        
        foreach ( $stones as $index => $stone ) {
            $stones_list[ $index ] = $stone['name'];
        }
        
        return $stones_list;
    }


    /**
     * Courses page
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function courses_page() {
        $stones_list = $this->get_stones_for_select();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Configurar Cursos', 'cm-precheckout' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'cm_precheckout_settings' );
                $options = get_option( 'cm_precheckout_options', array( 'courses' => array() ) );
                $courses = isset( $options['courses'] ) ? $options['courses'] : array();
                ?>
                
                <div id="courses-container">
                    <?php if ( empty( $courses ) ) : ?>
                        <div class="course-item">
                            <h3><?php esc_html_e( 'Curso 1', 'cm-precheckout' ); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="course_name_0"><?php esc_html_e( 'Nome do curso', 'cm-precheckout' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="course_name_0" 
                                               name="cm_precheckout_options[courses][0][name]" 
                                               class="regular-text" 
                                               value="" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="course_stone_0"><?php esc_html_e( 'Pedra', 'cm-precheckout' ); ?></label>
                                    </th>
                                    <td>
                                        <select id="course_stone_0" 
                                                name="cm_precheckout_options[courses][0][stone_id]" 
                                                class="regular-text">
                                            <?php foreach ( $stones_list as $stone_id => $stone_name ) : ?>
                                                <option value="<?php echo esc_attr( $stone_id ); ?>">
                                                    <?php echo esc_html( $stone_name ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">
                                            <?php esc_html_e( 'Selecione a pedra para este curso', 'cm-precheckout' ); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label><?php esc_html_e( 'Emblema 1', 'cm-precheckout' ); ?></label>
                                    </th>
                                    <td>
                                        <div class="media-upload">
                                            <input type="hidden" 
                                                   name="cm_precheckout_options[courses][0][emblem_1]" 
                                                   class="media-input" 
                                                   value="" />
                                            <button type="button" class="button media-upload-btn">
                                                <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                            </button>
                                            <div class="media-preview"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label><?php esc_html_e( 'Emblema 2', 'cm-precheckout' ); ?></label>
                                    </th>
                                    <td>
                                        <div class="media-upload">
                                            <input type="hidden" 
                                                   name="cm_precheckout_options[courses][0][emblem_2]" 
                                                   class="media-input" 
                                                   value="" />
                                            <button type="button" class="button media-upload-btn">
                                                <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                            </button>
                                            <div class="media-preview"></div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" class="button remove-course">
                                <?php esc_html_e( 'Remover curso', 'cm-precheckout' ); ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <?php foreach ( $courses as $index => $course ) : ?>
                            <div class="course-item">
                                <h3><?php echo esc_html( sprintf( __( 'Curso %d', 'cm-precheckout' ), $index + 1 ) ); ?></h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="course_name_<?php echo $index; ?>">
                                                <?php esc_html_e( 'Nome do curso', 'cm-precheckout' ); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" 
                                                   id="course_name_<?php echo $index; ?>" 
                                                   name="cm_precheckout_options[courses][<?php echo $index; ?>][name]" 
                                                   class="regular-text" 
                                                   value="<?php echo esc_attr( $course['name'] ); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="course_stone_<?php echo $index; ?>">
                                                <?php esc_html_e( 'Pedra', 'cm-precheckout' ); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <select id="course_stone_<?php echo $index; ?>" 
                                                    name="cm_precheckout_options[courses][<?php echo $index; ?>][stone_id]" 
                                                    class="regular-text">
                                                <?php foreach ( $stones_list as $stone_id => $stone_name ) : ?>
                                                    <option value="<?php echo esc_attr( $stone_id ); ?>" <?php selected( $course['stone_id'], $stone_id ); ?>>
                                                        <?php echo esc_html( $stone_name ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description">
                                                <?php esc_html_e( 'Selecione a pedra para este curso', 'cm-precheckout' ); ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php esc_html_e( 'Emblema 1', 'cm-precheckout' ); ?></label>
                                        </th>
                                        <td>
                                            <div class="media-upload">
                                                <input type="hidden" 
                                                       name="cm_precheckout_options[courses][<?php echo $index; ?>][emblem_1]" 
                                                       class="media-input" 
                                                       value="<?php echo esc_attr( $course['emblem_1'] ); ?>" />
                                                <button type="button" class="button media-upload-btn">
                                                    <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                                </button>
                                                <div class="media-preview">
                                                    <?php if ( $course['emblem_1'] ) : ?>
                                                        <?php echo wp_get_attachment_image( $course['emblem_1'], 'thumbnail' ); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php esc_html_e( 'Emblema 2', 'cm-precheckout' ); ?></label>
                                        </th>
                                        <td>
                                            <div class="media-upload">
                                                <input type="hidden" 
                                                       name="cm_precheckout_options[courses][<?php echo $index; ?>][emblem_2]" 
                                                       class="media-input" 
                                                       value="<?php echo esc_attr( $course['emblem_2'] ); ?>" />
                                                <button type="button" class="button media-upload-btn">
                                                    <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                                </button>
                                                <div class="media-preview">
                                                    <?php if ( $course['emblem_2'] ) : ?>
                                                        <?php echo wp_get_attachment_image( $course['emblem_2'], 'thumbnail' ); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                <button type="button" class="button remove-course">
                                    <?php esc_html_e( 'Remover curso', 'cm-precheckout' ); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" id="add-course" class="button button-primary">
                    <?php esc_html_e( 'Adicionar novo curso', 'cm-precheckout' ); ?>
                </button>

                <?php submit_button(); ?>
            </form>
        </div>

        <script type="text/template" id="course-template">
            <div class="course-item">
                <h3><?php esc_html_e( 'Novo Curso', 'cm-precheckout' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="course_name_{{index}}"><?php esc_html_e( 'Nome do curso', 'cm-precheckout' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                id="course_name_{{index}}" 
                                name="cm_precheckout_options[courses][{{index}}][name]" 
                                class="regular-text" 
                                value="" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="course_stone_{{index}}"><?php esc_html_e( 'Pedra', 'cm-precheckout' ); ?></label>
                        </th>
                        <td>
                            <select id="course_stone_{{index}}" 
                                    name="cm_precheckout_options[courses][{{index}}][stone_id]" 
                                    class="regular-text">
                                <?php foreach ( $this->get_stones_for_select() as $stone_id => $stone_name ) : ?>
                                    <option value="<?php echo esc_attr( $stone_id ); ?>">
                                        <?php echo esc_html( $stone_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Selecione a pedra para este curso', 'cm-precheckout' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Emblema 1', 'cm-precheckout' ); ?></label>
                        </th>
                        <td>
                            <div class="media-upload">
                                <input type="hidden" 
                                    name="cm_precheckout_options[courses][{{index}}][emblem_1]" 
                                    class="media-input" 
                                    value="" />
                                <button type="button" class="button media-upload-btn">
                                    <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                </button>
                                <div class="media-preview"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Emblema 2', 'cm-precheckout' ); ?></label>
                        </th>
                        <td>
                            <div class="media-upload">
                                <input type="hidden" 
                                    name="cm_precheckout_options[courses][{{index}}][emblem_2]" 
                                    class="media-input" 
                                    value="" />
                                <button type="button" class="button media-upload-btn">
                                    <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                </button>
                                <div class="media-preview"></div>
                            </div>
                        </td>
                    </tr>
                </table>
                <button type="button" class="button remove-course">
                    <?php esc_html_e( 'Remover curso', 'cm-precheckout' ); ?>
                </button>
            </div>
        </script>
        <?php
    }


    /**
     * Stones page
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function stones_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Configurar Pedras', 'cm-precheckout' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'cm_precheckout_settings' );
                $options = get_option( 'cm_precheckout_options', array( 'stones' => array() ) );
                $stones = isset( $options['stones'] ) ? $options['stones'] : array();
                ?>
                
                <div id="stones-container">
                    <?php if ( empty( $stones ) ) : ?>
                        <div class="stone-item">
                            <h3><?php esc_html_e( 'Pedra 1', 'cm-precheckout' ); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="stone_name_0"><?php esc_html_e( 'Nome da pedra', 'cm-precheckout' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="stone_name_0" 
                                               name="cm_precheckout_options[stones][0][name]" 
                                               class="regular-text" 
                                               value="" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label><?php esc_html_e( 'Ícone', 'cm-precheckout' ); ?></label>
                                    </th>
                                    <td>
                                        <div class="media-upload">
                                            <input type="hidden" 
                                                   name="cm_precheckout_options[stones][0][icon_url]" 
                                                   class="media-input-url" 
                                                   value="" />
                                            <button type="button" class="button media-upload-btn">
                                                <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                            </button>
                                            <div class="media-preview"></div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" class="button remove-stone">
                                <?php esc_html_e( 'Remover pedra', 'cm-precheckout' ); ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <?php foreach ( $stones as $index => $stone ) : ?>
                            <div class="stone-item">
                                <h3><?php echo esc_html( sprintf( __( 'Pedra %d', 'cm-precheckout' ), $index + 1 ) ); ?></h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="stone_name_<?php echo $index; ?>">
                                                <?php esc_html_e( 'Nome da pedra', 'cm-precheckout' ); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" 
                                                   id="stone_name_<?php echo $index; ?>" 
                                                   name="cm_precheckout_options[stones][<?php echo $index; ?>][name]" 
                                                   class="regular-text" 
                                                   value="<?php echo esc_attr( $stone['name'] ); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php esc_html_e( 'Ícone', 'cm-precheckout' ); ?></label>
                                        </th>
                                        <td>
                                            <div class="media-upload">
                                                <input type="hidden" 
                                                       name="cm_precheckout_options[stones][<?php echo $index; ?>][icon_url]" 
                                                       class="media-input-url" 
                                                       value="<?php echo esc_attr( $stone['icon_url'] ); ?>" />
                                                <button type="button" class="button media-upload-btn">
                                                    <?php esc_html_e( 'Selecionar imagem', 'cm-precheckout' ); ?>
                                                </button>
                                                <div class="media-preview">
                                                    <?php if ( $stone['icon_url'] ) : ?>
                                                        <img src="<?php echo esc_url( $stone['icon_url'] ); ?>" 
                                                             alt="<?php echo esc_attr( $stone['name'] ); ?>"
                                                             style="max-width: 100px; height: auto;" />
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                <button type="button" class="button remove-stone">
                                    <?php esc_html_e( 'Remover pedra', 'cm-precheckout' ); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" id="add-stone" class="button button-primary">
                    <?php esc_html_e( 'Adicionar nova pedra', 'cm-precheckout' ); ?>
                </button>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}