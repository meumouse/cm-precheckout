(function($) {
    'use strict';
    
    var CM_Precheckout = {
        init: function() {
            this.cacheElements();
            this.bindEvents();
        },
        
        cacheElements: function() {
            this.$button = $('.cm-precheckout-button');
            this.$modal = $('#cm-precheckout-modal');
            this.$overlay = $('.cm-precheckout-modal-overlay');
            this.$close = $('.cm-precheckout-close');
            this.$steps = $('.cm-precheckout-step');
            this.$nextButtons = $('.next-step');
            this.$prevButtons = $('.prev-step');
            this.$addToCart = $('.add-to-cart');
            this.currentStep = 1;
            this.productData = null;
            this.selections = {};
        },
        
        bindEvents: function() {
            var self = this;
            
            // Open modal
            this.$button.on('click', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                self.openModal(productId);
            });
            
            // Close modal
            this.$close.on('click', function() {
                self.closeModal();
            });
            
            this.$overlay.on('click', function() {
                self.closeModal();
            });
            
            // Next step
            this.$nextButtons.on('click', function() {
                if (self.validateStep(self.currentStep)) {
                    self.goToStep(self.currentStep + 1);
                }
            });
            
            // Previous step
            this.$prevButtons.on('click', function() {
                self.goToStep(self.currentStep - 1);
            });
            
            // Quantity controls
            $(document).on('click', '.quantity-minus', function() {
                var $input = $(this).siblings('.cm-quantity');
                var value = parseInt($input.val());
                if (value > 1) {
                    $input.val(value - 1);
                }
            });
            
            $(document).on('click', '.quantity-plus', function() {
                var $input = $(this).siblings('.cm-quantity');
                var value = parseInt($input.val());
                $input.val(value + 1);
            });
            
            // Add to cart
            this.$addToCart.on('click', function() {
                self.addToCart();
            });
        },
        
        openModal: function(productId) {
            var self = this;
            
            // Get product data
            $.ajax({
                url: cm_precheckout.ajax_url,
                type: 'POST',
                data: {
                    action: 'cm_precheckout_get_product_data',
                    product_id: productId,
                    nonce: cm_precheckout.nonce
                },
                beforeSend: function() {
                    self.$button.prop('disabled', true).text(cm_precheckout.i18n.loading);
                },
                success: function(response) {
                    if (response.success) {
                        self.productData = response.data;
                        self.populateModal();
                        self.$modal.show();
                        $('body').addClass('cm-precheckout-open');
                    }
                },
                complete: function() {
                    self.$button.prop('disabled', false).text('Personalizar anel');
                }
            });
        },
        
        closeModal: function() {
            this.$modal.hide();
            $('body').removeClass('cm-precheckout-open');
            this.resetModal();
        },
        
        resetModal: function() {
            this.currentStep = 1;
            this.selections = {};
            this.$steps.removeClass('active');
            this.$steps.first().addClass('active');
        },
        
        populateModal: function() {
            this.populateMaterials();
            this.populateSizes();
            this.populatePersonalization();
        },
        
        populateMaterials: function() {
            var $container = $('.material-options');
            $container.empty();
            
            var materials = this.productData.materials;
            var materialLabels = {
                'ouro_10k': 'Ouro 10k',
                'ouro_18k': 'Ouro 18k',
                'prata_950': 'Prata 950'
            };
            
            $.each(materials, function(index, material) {
                var label = materialLabels[material] || material;
                var html = '<div class="material-option">' +
                           '<input type="radio" id="material_' + material + '" name="material" value="' + material + '">' +
                           '<label for="material_' + material + '">' + label + '</label>' +
                           '</div>';
                $container.append(html);
            });
        },
        
        populateSizes: function() {
            var $container = $('.size-selectors');
            $container.empty();
            
            var count = this.productData.size_selectors || 1;
            
            for (var i = 1; i <= count; i++) {
                var html = '<div class="size-selector">' +
                           '<label for="size_' + i + '">' + cm_precheckout.i18n.size + ' ' + i + ':</label>' +
                           '<select id="size_' + i + '" name="size[]" class="cm-size-select">' +
                           '<option value="">' + cm_precheckout.i18n.select_size + '</option>';
                
                // Add sizes 13-33
                for (var size = 13; size <= 33; size++) {
                    html += '<option value="' + size + '">' + size + '</option>';
                }
                
                html += '</select></div>';
                $container.append(html);
            }
        },
        
        populatePersonalization: function() {
            var $container = $('.personalization-options');
            $container.empty();
            
            // Name engraving
            if (this.productData.enable_name_engraving === 'yes') {
                var nameFields = this.productData.name_fields || 1;
                var html = '<div class="name-engraving">' +
                           '<h4>' + cm_precheckout.i18n.name_engraving + '</h4>' +
                           '<div class="name-fields">';
                
                for (var i = 1; i <= nameFields; i++) {
                    html += '<input type="text" name="name_' + i + '" placeholder="' + cm_precheckout.i18n.name_placeholder + ' ' + i + '">';
                }
                
                html += '</div></div>';
                $container.append(html);
            }
            
            // Course change
            if (this.productData.enable_course_change === 'yes') {
                var courses = this.getCourses();
                if (courses.length > 0) {
                    var html = '<div class="course-selector">' +
                               '<h4>' + cm_precheckout.i18n.select_course + '</h4>' +
                               '<select name="course" class="cm-course-select">' +
                               '<option value="">' + cm_precheckout.i18n.select_course + '</option>';
                    
                    $.each(courses, function(index, course) {
                        var selected = (index == this.productData.default_course) ? 'selected' : '';
                        html += '<option value="' + index + '" ' + selected + '>' + course.name + '</option>';
                    }.bind(this));
                    
                    html += '</select></div>';
                    $container.append(html);
                }
            }
            
            // Stone sample
            if (this.productData.enable_stone_sample === 'yes') {
                var stones = this.getStones();
                if (stones.length > 0) {
                    var html = '<div class="stone-selector">' +
                               '<h4>' + cm_precheckout.i18n.select_stone + '</h4>' +
                               '<select name="stone" class="cm-stone-select">' +
                               '<option value="">' + cm_precheckout.i18n.select_stone + '</option>';
                    
                    $.each(stones, function(index, stone) {
                        html += '<option value="' + index + '">' + stone.name + '</option>';
                    });
                    
                    html += '</select></div>';
                    $container.append(html);
                }
            }
        },
        
        getCourses: function() {
            // This would typically come from an AJAX call
            // For now, return empty array
            return [];
        },
        
        getStones: function() {
            // This would typically come from an AJAX call
            // For now, return empty array
            return [];
        },
        
        goToStep: function(step) {
            if (step < 1 || step > 4) return;
            
            // Update summary if going to step 4
            if (step === 4) {
                this.updateSummary();
            }
            
            this.$steps.removeClass('active');
            $('.step-' + step).addClass('active');
            this.currentStep = step;
        },
        
        validateStep: function(step) {
            switch(step) {
                case 1:
                    var $material = $('input[name="material"]:checked');
                    if (!$material.length) {
                        alert(cm_precheckout.i18n.select_material);
                        return false;
                    }
                    this.selections.material = $material.val();
                    this.selections.quantity = $('.cm-quantity').val();
                    break;
                    
                case 2:
                    var sizes = [];
                    $('.cm-size-select').each(function() {
                        var value = $(this).val();
                        if (!value) {
                            alert(cm_precheckout.i18n.select_size);
                            sizes = [];
                            return false;
                        }
                        sizes.push(value);
                    });
                    
                    if (sizes.length === 0) return false;
                    this.selections.sizes = sizes;
                    break;
                    
                case 3:
                    // Store personalization selections
                    this.selections.names = [];
                    $('input[name^="name_"]').each(function() {
                        this.selections.names.push($(this).val());
                    }.bind(this));
                    
                    this.selections.course = $('.cm-course-select').val();
                    this.selections.stone = $('.cm-stone-select').val();
                    break;
            }
            
            return true;
        },
        
        updateSummary: function() {
            var materialLabels = {
                'ouro_10k': 'Ouro 10k',
                'ouro_18k': 'Ouro 18k',
                'prata_950': 'Prata 950'
            };
            
            $('.material-summary').text(materialLabels[this.selections.material] || this.selections.material);
            $('.quantity-summary').text(this.selections.quantity);
            $('.sizes-summary').text(this.selections.sizes.join(', '));
            $('.course-summary').text(this.selections.course || '-');
            $('.stone-summary').text(this.selections.stone || '-');
            
            // Calculate total
            var quantity = parseInt(this.selections.quantity);
            var price = parseFloat(this.productData.price);
            var total = quantity * price;
            
            $('.total-summary').text(this.productData.currency + total.toFixed(2));
        },
        
        addToCart: function() {
            var self = this;
            
            // Prepare data for AJAX request
            var data = {
                action: 'woocommerce_ajax_add_to_cart',
                product_id: this.productData.id,
                quantity: this.selections.quantity,
                'cm_precheckout_data': this.selections
            };
            
            $.ajax({
                url: cm_precheckout.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    self.$addToCart.prop('disabled', true).text(cm_precheckout.i18n.adding_to_cart);
                },
                success: function(response) {
                    if (response.success) {
                        alert(cm_precheckout.i18n.added_to_cart);
                        self.closeModal();
                        
                        // Update cart count
                        if (response.fragments) {
                            $.each(response.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                        }
                    } else {
                        alert(response.data.message || 'Error adding to cart');
                    }
                },
                error: function() {
                    alert('Error adding to cart');
                },
                complete: function() {
                    self.$addToCart.prop('disabled', false).text('Adicionar ao carrinho');
                }
            });
        }
    };
    
    $(document).ready(function() {
        CM_Precheckout.init();
    });
    
})(jQuery);