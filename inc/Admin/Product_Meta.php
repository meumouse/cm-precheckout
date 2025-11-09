<?php

namespace MeuMouse\Cm_Precheckout\Admin;

use WC_Product;
use WC_Product_Attribute;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * 
 */
class Product_Meta {

    private const TAB_ID = 'pre_checkout_steps';
    private const PANEL_ID = 'pre_checkout_steps_product_data';

    private const META_ENABLE = '_pre_checkout_steps_enabled';
    private const META_STEPS = '_pre_checkout_steps_config';

    private const FIELD_ENABLE = 'pre_checkout_steps_enabled';
    private const FIELD_STEP_PREFIX = 'pre_checkout_step_';

    public static function init(): void {
        add_filter('woocommerce_product_data_tabs', [self::class, 'registerTab']);
        add_action('woocommerce_product_data_panels', [self::class, 'renderPanel']);
        add_action('woocommerce_process_product_meta', [self::class, 'saveProductMeta']);
    }

    /**
     * @param array<string, array<string, mixed>> $tabs
     * @return array<string, array<string, mixed>>
     */
    public static function registerTab(array $tabs): array {
        $tabs[self::TAB_ID] = [
            'label' => __('Etapas pré checkout', 'charm-builder-modal'),
            'target' => self::PANEL_ID,
            'class' => ['show_if_simple', 'show_if_variable'],
            'priority' => 65,
        ];

        return $tabs;
    }

    public static function renderPanel(): void {
        global $post;

        if ( ! $post ) {
            return;
        }

        $productId = (int) $post->ID;
        $isEnabled = wc_string_to_bool( (string) get_post_meta( $productId, self::META_ENABLE, true ) );
        $storedSteps = get_post_meta( $productId, self::META_STEPS, true );
        $stepsConfig = is_array( $storedSteps ) ? $storedSteps : array();

        $materialOptions = self::getMaterialOptions($productId);

        wp_nonce_field('pre_checkout_steps', 'pre_checkout_steps_nonce'); ?>

        <div id="<?php echo \esc_attr(self::PANEL_ID); ?>" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php woocommerce_wp_checkbox([
                    'id' => self::FIELD_ENABLE,
                    'label' => __('Ativar etapas pré checkout', 'charm-builder-modal'),
                    'description' => __('Habilita o fluxo de etapas pré checkout para este produto.', 'charm-builder-modal'),
                    'value' => $isEnabled ? 'yes' : 'no',
                ]); ?>
            </div>

            <?php foreach ( self::getStepsSchema() as $stepKey => $stepData ) :
                $config = $stepsConfig[$stepKey] ?? array();
                $stepEnabled = isset($config['enabled']) ? \wc_string_to_bool((string) $config['enabled']) : false;
                $selectedMaterial = isset($config['material']) ? (string) $config['material'] : ''; ?>

                <div class="options_group">
                    <h3><?php echo \esc_html($stepData['label']); ?></h3>
                    <p class="description"><?php echo \esc_html($stepData['description']); ?></p>
                    <?php woocommerce_wp_checkbox([
                        'id' => self::FIELD_STEP_PREFIX . $stepKey . '_enabled',
                        'label' => __('Ativar etapa', 'charm-builder-modal'),
                        'value' => $stepEnabled ? 'yes' : 'no',
                    ]); ?>

                    <?php if (! empty($stepData['requires_material'])) : ?>
                        <?php if (! empty($materialOptions)) : ?>
                            <?php woocommerce_wp_select([
                                'id' => self::FIELD_STEP_PREFIX . $stepKey . '_material',
                                'label' => __('Material vinculado', 'charm-builder-modal'),
                                'description' => __('Selecione o material que será utilizado nesta etapa.', 'charm-builder-modal'),
                                'options' => ['' => __('Selecione uma opção', 'charm-builder-modal')] + $materialOptions,
                                'value' => $selectedMaterial,
                            ]); ?>
                        <?php else : ?>
                            <p class="form-field">
                                <span class="description">
                                    <?php echo \esc_html__(
                                        'Nenhum atributo de material foi encontrado. Adicione um atributo de material para associá-lo às etapas.',
                                        'charm-builder-modal'
                                    ); ?>
                                </span>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public static function saveProductMeta( int $productId ): void {
        if ( ! isset( $_POST['pre_checkout_steps_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pre_checkout_steps_nonce'] ?? '' ) ), 'pre_checkout_steps') ) {
            return;
        }

        $isEnabled = isset( $_POST[self::FIELD_ENABLE] ) ? 'yes' : 'no';
        update_post_meta( $productId, self::META_ENABLE, $isEnabled );

        $stepsConfig = array();

        foreach ( self::getStepsSchema() as $stepKey => $stepData ) {
            $stepEnabled = isset($_POST[self::FIELD_STEP_PREFIX . $stepKey . '_enabled']) ? 'yes' : 'no';
            $materialValue = '';

            if (! empty($stepData['requires_material'])) {
                $rawMaterial = isset($_POST[self::FIELD_STEP_PREFIX . $stepKey . '_material'])
                    ? \sanitize_text_field(\wp_unslash((string) $_POST[self::FIELD_STEP_PREFIX . $stepKey . '_material']))
                    : '';
                $materialValue = $rawMaterial;
            }

            $stepsConfig[$stepKey] = [
                'enabled' => $stepEnabled,
                'material' => $materialValue,
            ];
        }

        update_post_meta( $productId, self::META_STEPS, $stepsConfig );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function getStepsSchema(): array {
        return [
            'briefing' => [
                'label' => __('Briefing inicial', 'charm-builder-modal'),
                'description' => __('Checklist inicial com as preferências do cliente antes da personalização.', 'charm-builder-modal'),
                'requires_material' => false,
            ],
            'materials' => [
                'label' => __('Seleção de materiais', 'charm-builder-modal'),
                'description' => __('Associe quais materiais estarão disponíveis para o cliente antes do checkout.', 'charm-builder-modal'),
                'requires_material' => true,
            ],
            'approval' => [
                'label' => __('Aprovação final', 'charm-builder-modal'),
                'description' => __('Confirmação final dos detalhes do charm pelo cliente.', 'charm-builder-modal'),
                'requires_material' => false,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function getMaterialOptions(int $productId): array {
        $product = wc_get_product( $productId );

        if ( ! $product instanceof WC_Product ) {
            return array();
        }

        $options = array();

        foreach ( $product->get_attributes() as $attribute ) {
            if ( ! $attribute instanceof WC_Product_Attribute ) {
                continue;
            }

            if ( ! self::isMaterialAttribute( $attribute ) ) {
                continue;
            }

            $attributeLabel = wc_attribute_label( $attribute->get_name() );

            if ( $attribute->is_taxonomy() ) {
                $terms = get_terms([
                    'taxonomy' => $attribute->get_name(),
                    'include' => $attribute->get_options(),
                    'hide_empty' => false,
                ]);

                if ( is_wp_error( $terms ) ) {
                    continue;
                }

                foreach ( $terms as $term ) {
                    $options[$attribute->get_name() . '|' . $term->term_id] = sprintf('%s — %s', $attributeLabel, $term->name);
                }
            } else {
                foreach ( $attribute->get_options() as $option ) {
                    $options[$attribute->get_name() . '|' . \sanitize_title((string) $option)] = sprintf(
                        '%s — %s',
                        $attributeLabel,
                        $option
                    );
                }
            }
        }

        return $options;
    }

    private static function isMaterialAttribute( WC_Product_Attribute $attribute ): bool {
        $name = $attribute->get_name();
        $slug = $attribute->is_taxonomy() ? $name : \sanitize_title($name);

        return false !== strpos($slug, 'material');
    }
}