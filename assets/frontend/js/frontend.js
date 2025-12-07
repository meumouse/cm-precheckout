(function($) {
    'use strict';
    
    class CMPrecheckout {
        constructor() {
            this.currentStep = 1;
            this.totalSteps = 0;
            this.stepsData = {};
            this.currentProductId = 0;
            this.isLoading = false;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.setupSteps();
        }
        
        bindEvents() {
            // Open modal
            $(document).on('click', '.cm-precheckout-button', (e) => {
                e.preventDefault();
                this.openModal($(e.target).data('product-id'));
            });
            
            // Close modal
            $(document).on('click', '.cm-precheckout-close, .cm-precheckout-modal-overlay', () => {
                this.closeModal();
            });
            
            // Navigation
            $(document).on('click', '.next-step', () => {
                this.nextStep();
            });
            
            $(document).on('click', '.prev-step', () => {
                this.prevStep();
            });
            
            // Quantity controls
            $(document).on('click', '.quantity-plus', (e) => {
                this.changeQuantity($(e.target).closest('.quantity-input'), 1);
            });
            
            $(document).on('click', '.quantity-minus', (e) => {
                this.changeQuantity($(e.target).closest('.quantity-input'), -1);
            });
            
            // File upload
            $(document).on('change', '#customization_file', (e) => {
                this.handleFileUpload(e.target);
            });
            
            $(document).on('click', '.remove-file', () => {
                this.removeFile();
            });
            
            // Terms agreement
            $(document).on('change', 'input[name="agree_terms"]', () => {
                this.validateSummary();
            });
            
            // Add to cart
            $(document).on('click', '.add-to-cart', () => {
                this.addToCart();
            });
        }
        
        setupSteps() {
            const $steps = $('.cm-precheckout-step');
            this.totalSteps = $steps.length;
            
            // Initialize progress bar
            this.initProgressBar();
            
            // Calculate total on step changes
            $(document).on('change', '[name="material"], [name="sizes[]"], [name="course"], [name="stone"], [name="quantity"]', () => {
                if (this.currentStep === this.totalSteps) {
                    this.updateSummary();
                }
            });
            
            $(document).on('input', '[name="names[]"], [name="notes"]', () => {
                if (this.currentStep === this.totalSteps) {
                    this.updateSummary();
                }
            });
        }
        
        initProgressBar() {
            const $progressBar = $('.progress-bar');
            if ($progressBar.length) {
                const steps = this.totalSteps;
                const stepWidth = 100 / (steps - 1);
                
                let progressHTML = '<div class="progress-line"></div>';
                for (let i = 1; i <= steps; i++) {
                    progressHTML += `
                        <div class="progress-step" data-step="${i}">
                            <span class="step-number">${i}</span>
                            <span class="step-label"></span>
                        </div>
                    `;
                }
                
                $progressBar.html(progressHTML);
                this.updateProgressBar();
            }
        }
        
        updateProgressBar() {
            const $steps = $('.progress-step');
            const progress = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
            
            $('.progress-line').css('width', progress + '%');
            
            $steps.each(function() {
                const step = parseInt($(this).data('step'));
                if (step < this.currentStep) {
                    $(this).addClass('completed').removeClass('active');
                } else if (step === this.currentStep) {
                    $(this).addClass('active').removeClass('completed');
                } else {
                    $(this).removeClass('active completed');
                }
            });
        }
        
        openModal(productId) {
            this.currentProductId = productId;
            this.currentStep = 1;
            
            // Load product data
            this.loadProductData(productId).then(() => {
                $('#cm-precheckout-modal').show();
                $('body').addClass('cm-precheckout-modal-open');
                this.showStep(1);
                this.updateProgressBar();
            }).catch(error => {
                console.error('Error loading product data:', error);
                alert('Erro ao carregar dados do produto. Por favor, tente novamente.');
            });
        }
        
        closeModal() {
            $('#cm-precheckout-modal').hide();
            $('body').removeClass('cm-precheckout-modal-open');
            this.resetForm();
        }
        
        async loadProductData(productId) {
            try {
                const response = await $.ajax({
                    url: cm_precheckout.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cm_precheckout_get_product_data',
                        nonce: cm_precheckout.nonce,
                        product_id: productId
                    }
                });
                
                if (response.success) {
                    this.productData = response.data;
                    this.stepsData = {};
                    return response.data;
                } else {
                    throw new Error(response.data?.message || 'Unknown error');
                }
            } catch (error) {
                throw error;
            }
        }
        
        showStep(stepNumber) {
            // Hide all steps
            $('.cm-precheckout-step').removeClass('active').hide();
            
            // Show current step
            $(`.cm-precheckout-step.step-${stepNumber}`).addClass('active').show();
            
            // Update navigation buttons
            this.updateNavigation();
            
            // Update progress bar
            this.updateProgressBar();
            
            // Update summary if we're on the last step
            if (stepNumber === this.totalSteps) {
                this.updateSummary();
            }
        }
        
        updateNavigation() {
            const $prevBtn = $('.prev-step');
            const $nextBtn = $('.next-step');
            const $addToCartBtn = $('.add-to-cart');
            
            // Show/hide previous button
            if (this.currentStep > 1) {
                $prevBtn.show();
            } else {
                $prevBtn.hide();
            }
            
            // Change next button text on last step
            if (this.currentStep === this.totalSteps) {
                $nextBtn.hide();
                $addToCartBtn.show();
            } else {
                $nextBtn.show();
                $addToCartBtn.hide();
            }
        }
        
        async validateCurrentStep() {
            const $currentStep = $(`.cm-precheckout-step.step-${this.currentStep}`);
            const stepKey = $currentStep.data('key');
            const isRequired = $currentStep.data('required') === 'true';
            
            if (!isRequired) {
                return { valid: true };
            }
            
            const stepData = this.collectStepData(stepKey);
            
            try {
                const response = await $.ajax({
                    url: cm_precheckout.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cm_precheckout_process_step',
                        nonce: cm_precheckout.nonce,
                        step: `validate_${stepKey}`,
                        product_id: this.currentProductId,
                        data: stepData
                    }
                });
                
                return response.success ? 
                    { valid: true, data: response.data } : 
                    { valid: false, message: response.data?.message };
                    
            } catch (error) {
                console.error('Validation error:', error);
                return { 
                    valid: false, 
                    message: cm_precheckout.i18n.error_occurred 
                };
            }
        }
        
        collectStepData(stepKey) {
            const data = {};
            
            switch (stepKey) {
                case 'material_selection':
                    data.material = $('input[name="material"]:checked').val();
                    data.quantity = parseInt($('.cm-quantity').val()) || 1;
                    break;
                    
                case 'size_selection':
                    data.sizes = $('select[name="sizes[]"]').map(function() {
                        return $(this).val();
                    }).get();
                    break;
                    
                case 'personalization':
                    data.names = $('input[name="names[]"]').map(function() {
                        return $(this).val();
                    }).get();
                    data.course = $('select[name="course"]').val();
                    data.stone = $('input[name="stone"]:checked').val();
                    data.notes = $('textarea[name="notes"]').val();
                    break;
                    
                case 'file_upload':
                    data.file = $('#customization_file')[0].files[0];
                    break;
            }
            
            return data;
        }
        
        async nextStep() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading();
            
            try {
                const validation = await this.validateCurrentStep();
                
                if (!validation.valid) {
                    this.showValidationError(validation.message);
                    return;
                }
                
                // Store step data
                const $currentStep = $(`.cm-precheckout-step.step-${this.currentStep}`);
                const stepKey = $currentStep.data('key');
                this.stepsData[stepKey] = this.collectStepData(stepKey);
                
                // Move to next step
                this.currentStep++;
                this.showStep(this.currentStep);
                this.hideValidationError();
                
            } catch (error) {
                console.error('Step transition error:', error);
                this.showValidationError(cm_precheckout.i18n.error_occurred);
            } finally {
                this.isLoading = false;
                this.hideLoading();
            }
        }
        
        prevStep() {
            this.currentStep--;
            this.showStep(this.currentStep);
            this.hideValidationError();
        }
        
        showValidationError(message) {
            const $currentStep = $(`.cm-precheckout-step.step-${this.currentStep}`);
            const $validation = $currentStep.find('.step-validation');
            
            $validation.find('.validation-message')
                .removeClass('success')
                .addClass('error')
                .text(message);
            
            $validation.show();
            
            // Scroll to validation message
            $validation[0].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
        
        hideValidationError() {
            $(`.cm-precheckout-step.step-${this.currentStep} .step-validation`).hide();
        }
        
        showLoading() {
            $('.cm-precheckout-modal-content').addClass('cm-precheckout-loading');
            $('.next-step, .prev-step, .add-to-cart')
                .prop('disabled', true)
                .text(cm_precheckout.i18n.loading);
        }
        
        hideLoading() {
            $('.cm-precheckout-modal-content').removeClass('cm-precheckout-loading');
            $('.next-step, .prev-step, .add-to-cart')
                .prop('disabled', false)
                .text(function() {
                    if ($(this).hasClass('add-to-cart')) {
                        return cm_precheckout.i18n.add_to_cart;
                    } else if ($(this).hasClass('next-step')) {
                        return cm_precheckout.i18n.next;
                    } else {
                        return cm_precheckout.i18n.back;
                    }
                });
        }
        
        changeQuantity($input, delta) {
            const $quantityInput = $input.find('.cm-quantity');
            let quantity = parseInt($quantityInput.val()) || 1;
            
            quantity += delta;
            quantity = Math.max(1, Math.min(99, quantity));
            
            $quantityInput.val(quantity).trigger('change');
        }
        
        handleFileUpload(input) {
            const file = input.files[0];
            if (!file) return;
            
            const maxSize = parseInt($(input).data('max-size')) * 1024 * 1024; // Convert MB to bytes
            
            if (file.size > maxSize) {
                alert(`Arquivo muito grande. Tamanho máximo: ${$(input).data('max-size')}MB`);
                input.value = '';
                return;
            }
            
            $('.file-name').text(file.name);
            $('.file-preview').show();
        }
        
        removeFile() {
            $('#customization_file').val('');
            $('.file-preview').hide();
            $('.file-name').text('');
        }
        
        async updateSummary() {
            try {
                const allData = {
                    ...this.stepsData.material_selection,
                    ...this.stepsData.size_selection,
                    ...this.stepsData.personalization,
                    product_id: this.currentProductId
                };
                
                const response = await $.ajax({
                    url: cm_precheckout.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cm_precheckout_process_step',
                        nonce: cm_precheckout.nonce,
                        step: 'calculate_total',
                        product_id: this.currentProductId,
                        data: allData
                    }
                });
                
                if (response.success) {
                    this.updateSummaryDisplay(response.data);
                }
                
            } catch (error) {
                console.error('Error updating summary:', error);
            }
        }
        
        updateSummaryDisplay(data) {
            // Update product info
            $('.product-name').text(this.productData.title);
            $('.product-price').text(data.base_price);
            
            // Update material
            const material = this.stepsData.material_selection?.material;
            if (material) {
                const materialLabel = this.getMaterialLabel(material);
                $('.material-summary .value').text(materialLabel);
                $('.material-summary').show();
            } else {
                $('.material-summary').hide();
            }
            
            // Update quantity
            const quantity = this.stepsData.material_selection?.quantity || 1;
            $('.quantity-summary .value').text(quantity);
            
            // Update sizes
            const sizes = this.stepsData.size_selection?.sizes || [];
            if (sizes.length > 0) {
                $('.sizes-summary .value').text(sizes.join(', '));
                $('.sizes-summary').show();
            } else {
                $('.sizes-summary').hide();
            }
            
            // Update names
            const names = this.stepsData.personalization?.names || [];
            const filledNames = names.filter(name => name.trim() !== '');
            if (filledNames.length > 0) {
                $('.names-summary .value').text(filledNames.join(', '));
                $('.names-summary').show();
            } else {
                $('.names-summary').hide();
            }
            
            // Update totals
            $('.options-price').text(data.options_total);
            $('.total-price').text(data.total);
            
            // Show/hide sections based on data
            this.toggleSummarySections();
        }
        
        getMaterialLabel(materialKey) {
            // This would come from the product data
            return materialKey.split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        }
        
        toggleSummarySections() {
            $('.summary-item').each(function() {
                const $value = $(this).find('.value');
                if ($value.text().trim() === '') {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        }
        
        validateSummary() {
            const termsAgreed = $('input[name="agree_terms"]').is(':checked');
            $('.add-to-cart').prop('disabled', !termsAgreed);
        }
        
        async addToCart() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading();
            
            try {
                // Collect all data
                const cartData = {
                    product_id: this.currentProductId,
                    ...this.stepsData.material_selection,
                    ...this.stepsData.size_selection,
                    ...this.stepsData.personalization,
                    ...this.stepsData.file_upload
                };
                
                const response = await $.ajax({
                    url: cm_precheckout.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cm_precheckout_add_to_cart',
                        nonce: cm_precheckout.nonce,
                        ...cartData
                    }
                });
                
                if (response.success) {
                    this.showSuccessMessage(response.data.message);
                    
                    // Close modal after delay
                    setTimeout(() => {
                        this.closeModal();
                        
                        // Optionally redirect to cart
                        if (response.data.cart_url && cm_precheckout.settings.redirect_to_cart) {
                            window.location.href = response.data.cart_url;
                        }
                    }, 2000);
                    
                } else {
                    throw new Error(response.data?.message || 'Unknown error');
                }
                
            } catch (error) {
                this.showValidationError(error.message);
            } finally {
                this.isLoading = false;
                this.hideLoading();
            }
        }
        
        showSuccessMessage(message) {
            const $currentStep = $(`.cm-precheckout-step.step-${this.currentStep}`);
            const $validation = $currentStep.find('.step-validation');
            
            $validation.find('.validation-message')
                .removeClass('error')
                .addClass('success')
                .text(message);
            
            $validation.show();
        }
        
        resetForm() {
            this.currentStep = 1;
            this.stepsData = {};
            
            // Reset all form fields
            $('#cm-precheckout-modal form')[0]?.reset();
            $('.file-preview').hide();
            $('.step-validation').hide();
            
            // Reset progress bar
            this.updateProgressBar();
        }
    }
    
    // Initialize on document ready
    $(document).ready(() => {
        window.cmPrecheckout = new CMPrecheckout();
    });
    
})(jQuery);