<?php

namespace MeuMouse\Cm_Precheckout\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Product Tab for Pre-checkout Settings
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Product_Tab {

    /**
     * Constructor
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {
        // Add custom tab
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_tab' ), 99, 1 );

        // Add tab content
        add_action( 'woocommerce_product_data_panels', array( $this, 'add_tab_content' ) );

        // Save tab data
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_tab_data' ), 10, 2 );
    }


    /**
     * Add custom tab to product data
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param array $tabs
     * @return array
     */
    public function add_product_tab( $tabs ) {
        $tabs['cm_precheckout'] = array(
            'label'    => esc_html__( 'Etapas pré checkout', 'cm-precheckout' ),
            'target'   => 'cm_precheckout_product_data',
            'class'    => array( 'show_if_simple', 'show_if_variable' ),
            'priority' => 80,
        );

        return $tabs;
    }

    
    /**
     * Add tab content
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public function add_tab_content() {
        global $post;
        
        // get product object
        $product = wc_get_product( $post->ID );

        // Get saved values
        $precheckout_active = get_post_meta( $post->ID, '_cm_precheckout_active', true );
        $materials = get_post_meta( $post->ID, '_cm_precheckout_materials', true );
        $materials = is_array( $materials ) ? $materials : array();
        
        $size_selectors = get_post_meta( $post->ID, '_cm_precheckout_size_selectors', true );
        $size_selectors = $size_selectors ? absint( $size_selectors ) : 1;
        
        $enable_name_engraving = get_post_meta( $post->ID, '_cm_precheckout_enable_name_engraving', true );
        $name_fields = get_post_meta( $post->ID, '_cm_precheckout_name_fields', true );
        $name_fields = $name_fields ? absint( $name_fields ) : 1;
        
        $enable_course_change = get_post_meta( $post->ID, '_cm_precheckout_enable_course_change', true );
        $enable_stone_sample = get_post_meta( $post->ID, '_cm_precheckout_enable_stone_sample', true );
        $enable_emblems_sample = get_post_meta( $post->ID, '_cm_precheckout_enable_emblems_sample', true );
        $default_course = get_post_meta( $post->ID, '_cm_precheckout_default_course', true );
        
        // Get global courses
        $options = get_option( 'cm_precheckout_options', array() );
        $courses = isset( $options['courses'] ) ? $options['courses'] : array(); ?>
        
        <div id="cm_precheckout_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // Active/Deactivate
                woocommerce_wp_checkbox( array(
                    'id'            => '_cm_precheckout_active',
                    'label'         => esc_html__( 'Ativar/Desativar pré-checkout', 'cm-precheckout' ),
                    'description'   => esc_html__( 'Marque para ativar o pré-checkout para este produto', 'cm-precheckout' ),
                    'value'         => $precheckout_active ? 'yes' : 'no',
                    'default'       => 'yes',
                    'desc_tip'      => true,
                ) );
                ?>
            </div>

            <div class="precheckout-options" style="<?php echo $precheckout_active ? '' : 'display: none;'; ?>">
                
                <!-- Step 1: Materials -->
                <div class="options_group">
                    <h3><?php esc_html_e( 'Etapa 1: Material', 'cm-precheckout' ); ?></h3>
                    
                    <?php
                    // Get available materials via filter
                    $available_materials = apply_filters( 'cm_precheckout_available_materials', array(
                        'ouro_10k' => esc_html__( 'Ouro 10k', 'cm-precheckout' ),
                        'ouro_18k' => esc_html__( 'Ouro 18k', 'cm-precheckout' ),
                        'prata_950' => esc_html__( 'Prata 950', 'cm-precheckout' ),
                    ) );
                    
                    foreach ( $available_materials as $key => $label ) :
                        $checked = in_array( $key, $materials ) ? 'yes' : 'no';
                    ?>
                        <p class="form-field">
                            <label for="_cm_precheckout_material_<?php echo esc_attr( $key ); ?>">
                                <?php echo esc_html( $label ); ?>
                            </label>
                            <input type="checkbox" 
                                   id="_cm_precheckout_material_<?php echo esc_attr( $key ); ?>" 
                                   name="_cm_precheckout_materials[]" 
                                   value="<?php echo esc_attr( $key ); ?>"
                                   <?php checked( $checked, 'yes' ); ?> />
                            
                            <?php if ( $product->is_type( 'variable' ) ) : ?>
                                <select name="_cm_precheckout_material_attribute_<?php echo esc_attr( $key ); ?>">
                                    <option value=""><?php esc_html_e( 'Vincular a atributo', 'cm-precheckout' ); ?></option>
                                    <?php
                                    $attributes = $product->get_attributes();
                                    foreach ( $attributes as $attribute ) :
                                        if ( $attribute->get_variation() ) :
                                    ?>
                                        <option value="<?php echo esc_attr( $attribute->get_name() ); ?>">
                                            <?php echo esc_html( $attribute->get_name() ); ?>
                                        </option>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </select>
                            <?php endif; ?>
                        </p>
                    <?php endforeach; ?>
                </div>

                <!-- Step 2: Ring Sizes -->
                <div class="options_group">
                    <h3><?php esc_html_e( 'Etapa 2: Seleção de tamanhos de anel', 'cm-precheckout' ); ?></h3>
                    
                    <?php
                    woocommerce_wp_select( array(
                        'id'            => '_cm_precheckout_size_selectors',
                        'label'         => esc_html__( 'Número de seletores de tamanho', 'cm-precheckout' ),
                        'options'       => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                        ),
                        'value'         => $size_selectors,
                        'desc_tip'      => true,
                        'description'   => esc_html__( 'Quantos campos de seleção de tamanho serão exibidos', 'cm-precheckout' ),
                    ) );
                    ?>
                </div>

                <!-- Step 3: Personalization -->
                <div class="options_group">
                    <h3><?php esc_html_e( 'Etapa 3: Personalização', 'cm-precheckout' ); ?></h3>
                    
                    <?php
                    // Name engraving
                    woocommerce_wp_checkbox( array(
                        'id'            => '_cm_precheckout_enable_name_engraving',
                        'label'         => esc_html__( 'Ativar/Desativar gravação de nomes', 'cm-precheckout' ),
                        'value'         => $enable_name_engraving ? 'yes' : 'no',
                        'desc_tip'      => true,
                        'description'   => esc_html__( 'Permitir que os clientes gravem nomes no anel', 'cm-precheckout' ),
                    ) );
                    ?>
                    
                    <div class="name-engraving-options" style="<?php echo $enable_name_engraving ? '' : 'display: none;'; ?>">
                        <?php
                        woocommerce_wp_select( array(
                            'id'            => '_cm_precheckout_name_fields',
                            'label'         => esc_html__( 'Número de campos para gravação de nomes', 'cm-precheckout' ),
                            'options'       => array(
                                '1' => '1',
                                '2' => '2',
                                '3' => '3',
                                '4' => '4',
                            ),
                            'value'         => $name_fields,
                            'desc_tip'      => true,
                            'description'   => esc_html__( 'Quantos campos de texto serão exibidos para gravação', 'cm-precheckout' ),
                        ) );
                        ?>
                    </div>
                    
                    <?php
                    // Course change
                    woocommerce_wp_checkbox( array(
                        'id'            => '_cm_precheckout_enable_course_change',
                        'label'         => esc_html__( 'Ativar alteração de nome do curso', 'cm-precheckout' ),
                        'value'         => $enable_course_change ? 'yes' : 'no',
                        'desc_tip'      => true,
                        'description'   => esc_html__( 'Permitir que os clientes alterem o curso do anel', 'cm-precheckout' ),
                    ) );
                    
                    // Stone sample
                    woocommerce_wp_checkbox( array(
                        'id'            => '_cm_precheckout_enable_stone_sample',
                        'label'         => esc_html__( 'Ativar amostra de cor de pedras', 'cm-precheckout' ),
                        'value'         => $enable_stone_sample ? 'yes' : 'no',
                        'desc_tip'      => true,
                        'description'   => esc_html__( 'Exibir amostras de cores de pedras disponíveis', 'cm-precheckout' ),
                    ) );
                    
                    // Emblems sample
                    woocommerce_wp_checkbox( array(
                        'id'            => '_cm_precheckout_enable_emblems_sample',
                        'label'         => esc_html__( 'Ativar amostra de emblemas e cursos', 'cm-precheckout' ),
                        'value'         => $enable_emblems_sample ? 'yes' : 'no',
                        'desc_tip'      => true,
                        'description'   => esc_html__( 'Exibir amostras de emblemas e cursos disponíveis', 'cm-precheckout' ),
                    ) );
                    ?>
                    
                    <div class="emblems-options" style="<?php echo $enable_emblems_sample ? '' : 'display: none;'; ?>">
                        <?php
                        $course_options = array( '' => esc_html__( 'Selecione um curso', 'cm-precheckout' ) );
                        foreach ( $courses as $index => $course ) {
                            $course_options[ $index ] = $course['name'];
                        }
                        
                        woocommerce_wp_select( array(
                            'id'            => '_cm_precheckout_default_course',
                            'label'         => esc_html__( 'Definir curso padrão do anel', 'cm-precheckout' ),
                            'options'       => $course_options,
                            'value'         => $default_course,
                            'desc_tip'      => true,
                            'description'   => esc_html__( 'Curso que será pré-selecionado no frontend', 'cm-precheckout' ),
                        ) );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery( document ).ready( function( $ ) {
                // Toggle precheckout options
                $( '#_cm_precheckout_active' ).change( function() {
                    if ( $( this ).is( ':checked' ) ) {
                        $( '.precheckout-options' ).show();
                    } else {
                        $( '.precheckout-options' ).hide();
                    }
                } );

                // Toggle name engraving options
                $( '#_cm_precheckout_enable_name_engraving' ).change( function() {
                    if ( $( this ).is( ':checked' ) ) {
                        $( '.name-engraving-options' ).show();
                    } else {
                        $( '.name-engraving-options' ).hide();
                    }
                } );

                // Toggle emblems options
                $( '#_cm_precheckout_enable_emblems_sample' ).change( function() {
                    if ( $( this ).is( ':checked' ) ) {
                        $( '.emblems-options' ).show();
                    } else {
                        $( '.emblems-options' ).hide();
                    }
                } );
            } );
        </script>
        <?php
    }
    

    /**
     * Save tab data
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $post_id
     * @param object $post
     * @return void
     */
    public function save_tab_data( $post_id, $post ) {
        // Save active status
        $active = isset( $_POST['_cm_precheckout_active'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_cm_precheckout_active', $active );

        // Save materials
        $materials = isset( $_POST['_cm_precheckout_materials'] ) ? array_map( 'sanitize_text_field', $_POST['_cm_precheckout_materials'] ) : array();
        update_post_meta( $post_id, '_cm_precheckout_materials', $materials );

        // Save size selectors
        if ( isset( $_POST['_cm_precheckout_size_selectors'] ) ) {
            update_post_meta( $post_id, '_cm_precheckout_size_selectors', absint( $_POST['_cm_precheckout_size_selectors'] ) );
        }

        // Save name engraving
        $enable_name_engraving = isset( $_POST['_cm_precheckout_enable_name_engraving'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_cm_precheckout_enable_name_engraving', $enable_name_engraving );

        // Save name fields
        if ( isset( $_POST['_cm_precheckout_name_fields'] ) ) {
            update_post_meta( $post_id, '_cm_precheckout_name_fields', absint( $_POST['_cm_precheckout_name_fields'] ) );
        }

        // Save course change
        $enable_course_change = isset( $_POST['_cm_precheckout_enable_course_change'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_cm_precheckout_enable_course_change', $enable_course_change );

        // Save stone sample
        $enable_stone_sample = isset( $_POST['_cm_precheckout_enable_stone_sample'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_cm_precheckout_enable_stone_sample', $enable_stone_sample );

        // Save emblems sample
        $enable_emblems_sample = isset( $_POST['_cm_precheckout_enable_emblems_sample'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_cm_precheckout_enable_emblems_sample', $enable_emblems_sample );

        // Save default course
        if ( isset( $_POST['_cm_precheckout_default_course'] ) ) {
            update_post_meta( $post_id, '_cm_precheckout_default_course', sanitize_text_field( $_POST['_cm_precheckout_default_course'] ) );
        }
    }
}