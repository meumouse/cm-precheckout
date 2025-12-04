<?php

namespace MeuMouse\Cm_Precheckout\Helpers;

/**
 * Utility functions
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse\Cm_Precheckout
 */
class Utils {

    /**
     * Get available materials
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_available_materials() {
        $materials = array(
            'ouro_10k' => esc_html__( 'Ouro 10k', 'cm-precheckout' ),
            'ouro_18k' => esc_html__( 'Ouro 18k', 'cm-precheckout' ),
            'prata_950' => esc_html__( 'Prata 950', 'cm-precheckout' ),
        );
        
        return apply_filters( 'cm_precheckout_available_materials', $materials );
    }


    /**
     * Get ring sizes
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_ring_sizes() {
        $sizes = array();
        
        for ( $i = 13; $i <= 33; $i++ ) {
            $sizes[ $i ] = sprintf( esc_html__( 'Tamanho %d', 'cm-precheckout' ), $i );
        }
        
        return apply_filters( 'cm_precheckout_ring_sizes', $sizes );
    }


    /**
     * Get courses
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_courses() {
        $options = get_option( 'cm_precheckout_options', array() );

        return isset( $options['courses'] ) ? $options['courses'] : array();
    }


    /**
     * Get stones
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_stones() {
        $options = get_option( 'cm_precheckout_options', array() );
        return isset( $options['stones'] ) ? $options['stones'] : array();

    }


    /**
     * Check if product has precheckout enabled
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $product_id
     * @return bool
     */
    public static function is_precheckout_enabled( $product_id ) {
        $enabled = get_post_meta( $product_id, '_cm_precheckout_active', true );

        return $enabled === 'yes';
    }
    

    /**
     * Get product materials
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $product_id
     * @return array
     */
    public static function get_product_materials( $product_id ) {
        $materials = get_post_meta( $product_id, '_cm_precheckout_materials', true );

        return is_array( $materials ) ? $materials : array();
    }
}