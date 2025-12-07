/**
 * Product tab interactions for CM Precheckout.
 *
 * @namespace CMPrecheckoutProductTab
 */
(function (window, $) {
    'use strict';

    const ns = {};
    let editingAction = null;

    /**
     * Initialize module events.
     *
     * @returns {void}
     */
    ns.init = function () {
        ns.togglePrecheckoutOptions();
        ns.initSortable();
        ns.bindEvents();
        ns.syncStepsData();
    };

    /**
     * Toggle precheckout options visibility.
     *
     * @returns {void}
     */
    ns.togglePrecheckoutOptions = function () {
        const $checkbox = $('#_cm_precheckout_active');

        if (!$checkbox.length) {
            return;
        }

        $('.precheckout-options').toggle($checkbox.is(':checked'));

        $checkbox.on('change', function () {
            $('.precheckout-options').toggle($checkbox.is(':checked'));
        });
    };

    /**
     * Initialize sortable handler for steps.
     *
     * @returns {void}
     */
    ns.initSortable = function () {
        const $container = $('#product-steps-container');

        if (!$container.length || typeof $.fn.sortable === 'undefined') {
            return;
        }

        $container.sortable({
            handle: '.cm-precheckout-step__handle',
            update: ns.syncStepsData
        }).disableSelection();
    };

    /**
     * Bind DOM events.
     *
     * @returns {void}
     */
    ns.bindEvents = function () {
        $(document).on('click', '#cm-precheckout-add-step', ns.openStepModal);
        $(document).on('click', '#cm-precheckout-save-step', ns.createStep);
        $(document).on('click', '.cm-precheckout-remove-step', ns.removeStep);

        $(document).on('click', '.cm-precheckout-add-action', ns.prepareNewAction);
        $(document).on('click', '.cm-precheckout-edit-action', ns.prepareEditAction);
        $(document).on('click', '#cm-precheckout-save-action', ns.saveAction);
        $(document).on('click', '.cm-precheckout-remove-action', ns.removeAction);
        $(document).on('change', '#cm-precheckout-action-key', ns.fillActionDisplayName);

        $(document).on('click', '.configure-materials', ns.openMaterialsModal);
        $(document).on('click', '.save-materials-config', ns.saveMaterialsConfig);

        $(document).on('click', '.cm-modal-close, .cm-modal-overlay', ns.closeModals);
        $(document).on('click', '.cm-modal-content', function (event) {
            event.stopPropagation();
        });
    };

    /**
     * Open modal for creating a new step.
     *
     * @returns {void}
     */
    ns.openStepModal = function () {
        $('#cm-precheckout-step-name').val('');
        $('#cm-precheckout-step-icon').val('');
        $('#cm-precheckout-step-modal').show();
    };

    /**
     * Create step container via AJAX.
     *
     * @returns {void}
     */
    ns.createStep = function () {
        const name = $('#cm-precheckout-step-name').val();
        const icon = $('#cm-precheckout-step-icon').val();

        if (!name) {
            $('#cm-precheckout-step-name').focus();
            return;
        }

        ns.request({
            action: 'cm_precheckout_create_step_container',
            step_name: name,
            step_icon: icon,
            product_id: ns.getProductId()
        }).done(function (response) {
            if (response.success) {
                $('#product-steps-container').append(response.data.html);
                ns.syncStepsData();
                ns.initSortable();
                ns.closeModals();
            }
        });
    };

    /**
     * Remove a step after confirmation.
     *
     * @param {Event} event Click event.
     * @returns {void}
     */
    ns.removeStep = function (event) {
        event.preventDefault();

        if (!window.confirm(ns.getText('remove_step'))) {
            return;
        }

        $(event.currentTarget).closest('.cm-precheckout-step').remove();
        ns.syncStepsData();
    };

    /**
     * Prepare modal for creating a new action.
     *
     * @param {Event} event Click event.
     * @returns {void}
     */
    ns.prepareNewAction = function (event) {
        editingAction = {
            mode: 'create',
            step: $(event.currentTarget).closest('.cm-precheckout-step'),
            target: null
        };

        ns.populateActionSelect();
        ns.resetActionFields();
        ns.fillActionDisplayName();
        $('#cm-precheckout-action-modal-title').text(ns.getText('add_action'));
        $('#cm-precheckout-action-modal').show();
    };

    /**
     * Fill action display name from selected option when empty.
     *
     * @returns {void}
     */
    ns.fillActionDisplayName = function () {
        const $displayName = $('#cm-precheckout-action-display-name');

        if ($displayName.val()) {
            return;
        }

        const selected = $('#cm-precheckout-action-key').val();
        const options = ns.getOptions();

        if (selected && options[selected]) {
            $displayName.val(options[selected].config.display_name || options[selected].name || '');
        }
    };

    /**
     * Prepare modal for editing an existing action.
     *
     * @param {Event} event Click event.
     * @returns {void}
     */
    ns.prepareEditAction = function (event) {
        const $action = $(event.currentTarget).closest('.cm-precheckout-action');
        const actionKey = $action.data('action-key');

        editingAction = {
            mode: 'edit',
            step: $action.closest('.cm-precheckout-step'),
            target: $action
        };

        ns.populateActionSelect(actionKey, true);
        $('#cm-precheckout-action-required').prop('checked', $action.data('action-required') === 1 || $action.data('action-required') === '1');
        $('#cm-precheckout-action-display-name').val($action.data('action-display-name'));
        $('#cm-precheckout-action-message').val($action.data('action-message'));

        ns.fillActionDisplayName();
        $('#cm-precheckout-action-modal-title').text(ns.getText('edit_action'));
        $('#cm-precheckout-action-modal').show();
    };

    /**
     * Populate options select field.
     *
     * @param {string|null} selected Selected option key.
     * @param {boolean} disable Whether to disable selection.
     * @returns {void}
     */
    ns.populateActionSelect = function (selected = null, disable = false) {
        const $select = $('#cm-precheckout-action-key');
        const options = ns.getOptions();

        $select.empty();
        $.each(options, function (key, option) {
            const optionElement = $('<option/>').val(key).text(option.name);
            if (selected === key) {
                optionElement.prop('selected', true);
            }
            $select.append(optionElement);
        });

        $select.prop('disabled', disable);
    };

    /**
     * Reset action form fields.
     *
     * @returns {void}
     */
    ns.resetActionFields = function () {
        $('#cm-precheckout-action-key').val('');
        $('#cm-precheckout-action-required').prop('checked', false);
        $('#cm-precheckout-action-display-name').val('');
        $('#cm-precheckout-action-message').val('');
    };

    /**
     * Save action (create or update).
     *
     * @returns {void}
     */
    ns.saveAction = function () {
        if (!editingAction) {
            return;
        }

        const optionKey = $('#cm-precheckout-action-key').val();
        const isRequired = $('#cm-precheckout-action-required').is(':checked');
        const displayName = $('#cm-precheckout-action-display-name').val();
        const additionalMessage = $('#cm-precheckout-action-message').val();

        if (!optionKey) {
            $('#cm-precheckout-action-key').focus();
            return;
        }

        ns.request({
            action: 'cm_precheckout_get_step_action',
            option_key: optionKey,
            required: isRequired ? 1 : 0,
            display_name: displayName,
            additional_message: additionalMessage,
            product_id: ns.getProductId()
        }).done(function (response) {
            if (!response.success) {
                return;
            }

            if (editingAction.mode === 'edit' && editingAction.target) {
                editingAction.target.replaceWith(response.data.html);
            } else {
                editingAction.step.find('.cm-precheckout-step__actions-list').append(response.data.html);
            }

            ns.syncStepsData();
            ns.closeModals();
        });
    };

    /**
     * Remove action after confirmation.
     *
     * @param {Event} event Click event.
     * @returns {void}
     */
    ns.removeAction = function (event) {
        event.preventDefault();

        if (!window.confirm(ns.getText('remove_action'))) {
            return;
        }

        $(event.currentTarget).closest('.cm-precheckout-action').remove();
        ns.syncStepsData();
    };

    /**
     * Open materials configuration modal.
     *
     * @returns {void}
     */
    ns.openMaterialsModal = function () {
        ns.request({
            action: 'cm_precheckout_get_materials_config',
            product_id: ns.getProductId()
        }).done(function (response) {
            if (response.success) {
                $('#materials-config-container').html(response.data.html);
                $('#materials-config-modal').show();
            }
        });
    };

    /**
     * Save materials configuration.
     *
     * @returns {void}
     */
    ns.saveMaterialsConfig = function () {
        const formData = $('#materials-config-form').serialize();

        ns.request({
            action: 'cm_precheckout_save_materials_config',
            product_id: ns.getProductId()
        }, formData).done(function (response) {
            if (response.success) {
                window.alert('Configurações salvas com sucesso!');
                ns.closeModals();
            }
        });
    };

    /**
     * Close all modals.
     *
     * @returns {void}
     */
    ns.closeModals = function () {
        $('.cm-modal').hide();
        editingAction = null;
    };

    /**
     * Synchronize steps data into hidden input.
     *
     * @returns {void}
     */
    ns.syncStepsData = function () {
        const steps = [];

        $('#product-steps-container .cm-precheckout-step').each(function () {
            const $step = $(this);
            const actions = [];

            $step.find('.cm-precheckout-action').each(function () {
                const $action = $(this);
                actions.push({
                    key: $action.data('action-key'),
                    required: $action.data('action-required') === 1 || $action.data('action-required') === '1',
                    display_name: $action.data('action-display-name'),
                    additional_message: $action.data('action-message')
                });
            });

            steps.push({
                id: $step.data('step-id'),
                name: $step.data('step-name'),
                icon: $step.data('step-icon'),
                actions: actions
            });
        });

        $('#product_steps_data').val(JSON.stringify(steps));
    };

    /**
     * Generic AJAX helper.
     *
     * @param {Object} data Request data.
     * @param {string} [formData] Optional serialized form data.
     * @returns {jqXHR}
     */
    ns.request = function (data, formData = '') {
        const payload = $.param($.extend({}, data, {
            nonce: ns.getNonce()
        }));

        const requestData = formData ? formData + '&' + payload : payload;

        return $.ajax({
            url: ns.getAjaxUrl(),
            method: 'POST',
            data: requestData
        });
    };

    /**
     * Get localized options.
     *
     * @returns {Object}
     */
    ns.getOptions = function () {
        if (window.cm_precheckout_product_tab && window.cm_precheckout_product_tab.options) {
            return window.cm_precheckout_product_tab.options;
        }

        return {};
    };

    /**
     * Retrieve product ID.
     *
     * @returns {number}
     */
    ns.getProductId = function () {
        if (window.cm_precheckout_product_tab && window.cm_precheckout_product_tab.product_id) {
            return parseInt(window.cm_precheckout_product_tab.product_id, 10);
        }

        return 0;
    };

    /**
     * Retrieve nonce.
     *
     * @returns {string}
     */
    ns.getNonce = function () {
        if (window.cm_precheckout_product_tab && window.cm_precheckout_product_tab.nonce) {
            return window.cm_precheckout_product_tab.nonce;
        }

        return '';
    };

    /**
     * Get AJAX URL.
     *
     * @returns {string}
     */
    ns.getAjaxUrl = function () {
        if (window.cm_precheckout_admin && window.cm_precheckout_admin.ajax_url) {
            return window.cm_precheckout_admin.ajax_url;
        }

        if (window.ajaxurl) {
            return window.ajaxurl;
        }

        return '';
    };

    /**
     * Get translated text.
     *
     * @param {string} key Text key.
     * @returns {string}
     */
    ns.getText = function (key) {
        if (window.cm_precheckout_product_tab && window.cm_precheckout_product_tab.i18n && window.cm_precheckout_product_tab.i18n[key]) {
            return window.cm_precheckout_product_tab.i18n[key];
        }

        return '';
    };

    $(ns.init);
})(window, jQuery);