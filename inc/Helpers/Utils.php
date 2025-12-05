<?php

namespace MeuMouse\Cm_Precheckout\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Utility functions
 * 
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Utils {

    /**
     * Cache for options
     * 
     * @since 1.0.0
     * @var array
     */
    private static $options = null;

    /**
     * Cache for materials
     * 
     * @since 1.0.0
     * @var array
     */
    private static $materials_cache = array();

    /**
     * Get plugin options with caching
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    private static function get_options() {
        if ( self::$options === null ) {
            self::$options = get_option( 'cm_precheckout_options', array() );
        }
        
        return self::$options;
    }


    /**
     * Get available materials with caching
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_available_materials() {
        if ( ! empty( self::$materials_cache ) ) {
            return self::$materials_cache;
        }

        $materials = array(
            'ouro_10k' => esc_html__( 'Ouro 10k', 'cm-precheckout' ),
            'ouro_18k' => esc_html__( 'Ouro 18k', 'cm-precheckout' ),
            'prata_950' => esc_html__( 'Prata 950', 'cm-precheckout' ),
        );
        
        self::$materials_cache = apply_filters( 'cm_precheckout_available_materials', $materials );
        
        return self::$materials_cache;
    }


    /**
     * Get material label by key
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param string $material_key
     * @return string
     */
    public static function get_material_label( $material_key ) {
        $materials = self::get_available_materials();
        
        return isset( $materials[ $material_key ] ) ? $materials[ $material_key ] : $material_key;
    }
    

    /**
     * Get ring sizes with caching
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_ring_sizes() {
        static $sizes = null;
        
        if ( $sizes === null ) {
            $sizes = array();

            for ( $i = 13; $i <= 33; $i++ ) {
                $sizes[ $i ] = sprintf( esc_html__( 'Tamanho %d', 'cm-precheckout' ), $i );
            }

            $sizes = apply_filters( 'cm_precheckout_ring_sizes', $sizes );
        }
        
        return $sizes;
    }


    /**
     * Get courses with validation
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_courses() {
        $options = self::get_options();
        $courses = isset( $options['courses'] ) ? $options['courses'] : array();
        
        // Validate course structure
        return array_filter( $courses, function( $course ) {
            return is_array( $course ) && ! empty( $course['name'] );
        });
    }


    /**
     * Get stones with validation
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return array
     */
    public static function get_stones() {
        $options = self::get_options();
        $stones = isset( $options['stones'] ) ? $options['stones'] : array();
        
        // Validate stone structure
        return array_filter( $stones, function( $stone ) {
            return is_array( $stone ) && ! empty( $stone['name'] );
        });
    }


    /**
     * Get course by ID
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $course_id
     * @return array|null
     */
    public static function get_course_by_id( $course_id ) {
        $courses = self::get_courses();
        
        return isset( $courses[ $course_id ] ) ? $courses[ $course_id ] : null;
    }


    /**
     * Get stone by ID
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $stone_id
     * @return array|null
     */
    public static function get_stone_by_id( $stone_id ) {
        $stones = self::get_stones();
        
        return isset( $stones[ $stone_id ] ) ? $stones[ $stone_id ] : null;
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
     * Get product materials with validation
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $product_id
     * @return array
     */
    public static function get_product_materials( $product_id ) {
        $materials = get_post_meta( $product_id, '_cm_precheckout_materials', true );
        $materials = is_array( $materials ) ? $materials : array();
        
        // Filter only available materials
        $available_materials = self::get_available_materials();
        
        return array_filter( $materials, function( $material ) use ( $available_materials ) {
            return isset( $available_materials[ $material ] );
        });
    }


    /**
     * Get product personalization settings
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $product_id
     * @return array
     */
    public static function get_product_personalization( $product_id ) {
        return array(
            'enable_name_engraving' => get_post_meta( $product_id, '_cm_precheckout_enable_name_engraving', true ) === 'yes',
            'name_fields' => absint( get_post_meta( $product_id, '_cm_precheckout_name_fields', true ) ) ?: 1,
            'enable_course_change' => get_post_meta( $product_id, '_cm_precheckout_enable_course_change', true ) === 'yes',
            'enable_stone_sample' => get_post_meta( $product_id, '_cm_precheckout_enable_stone_sample', true ) === 'yes',
            'enable_emblems_sample' => get_post_meta( $product_id, '_cm_precheckout_enable_emblems_sample', true ) === 'yes',
            'default_course' => get_post_meta( $product_id, '_cm_precheckout_default_course', true ),
            'size_selectors' => absint( get_post_meta( $product_id, '_cm_precheckout_size_selectors', true ) ) ?: 1,
        );
    }


    /**
     * Get material price for product
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param int $product_id
     * @param string $material
     * @return float
     */
    public static function get_material_price( $product_id, $material ) {
        $material_prices = get_post_meta( $product_id, '_cm_precheckout_material_prices', true );
        
        if ( is_array( $material_prices ) && isset( $material_prices[ $material ] ) ) {
            return floatval( $material_prices[ $material ] );
        }
        
        return 0;
    }


    /**
     * Sanitize customization data
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @param array $data
     * @return array
     */
    public static function sanitize_customization_data( $data ) {
        $sanitized = array();
        
        if ( isset( $data['material'] ) ) {
            $available_materials = self::get_available_materials();

            if ( isset( $available_materials[ $data['material'] ] ) ) {
                $sanitized['material'] = sanitize_text_field( $data['material'] );
            }
        }
        
        if ( isset( $data['sizes'] ) && is_array( $data['sizes'] ) ) {
            $sanitized['sizes'] = array();

            foreach ( $data['sizes'] as $size ) {
                $size = absint( $size );
                if ( $size >= 13 && $size <= 33 ) {
                    $sanitized['sizes'][] = $size;
                }
            }
        }
        
        if ( isset( $data['names'] ) && is_array( $data['names'] ) ) {
            $sanitized['names'] = array_map( 'sanitize_text_field', $data['names'] );
        }
        
        if ( isset( $data['course'] ) ) {
            $course = self::get_course_by_id( $data['course'] );

            if ( $course ) {
                $sanitized['course'] = sanitize_text_field( $data['course'] );
            }
        }
        
        if ( isset( $data['stone'] ) ) {
            $stone = self::get_stone_by_id( $data['stone'] );

            if ( $stone ) {
                $sanitized['stone'] = sanitize_text_field( $data['stone'] );
            }
        }
        
        if ( isset( $data['notes'] ) ) {
            $sanitized['notes'] = sanitize_textarea_field( $data['notes'] );
        }
        
        return $sanitized;
    }


    /**
     * Clear cache
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @return void
     */
    public static function clear_cache() {
        self::$options = null;
        self::$materials_cache = array();
    }
}