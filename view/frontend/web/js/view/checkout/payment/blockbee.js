define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/action/redirect-on-success',
    'jquery',
    'mage/translate'
], function (Component, quote, additionalValidators, placeOrderAction, redirectOnSuccessAction, $, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Blockbee_Blockbee/checkout/payment/blockbee'
        },
        getInstructions: function () {
            return window.checkoutConfig.payment.blockbee.instructions;
        },
        getData: function () {
            return {
                "method": 'blockbee',
                "additional_data": {}
            };
        },
        placeOrder: function (data, event) {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);

                placeOrderAction(this.getData(), this.messageContainer)
                    .done(function () {
                        redirectOnSuccessAction.execute();
                    })
                    .fail(function (response) {
                        self.isPlaceOrderActionAllowed(true);
                        self.messageContainer.addErrorMessage({
                            message: $t('An error occurred while processing your payment. Please try again.')
                        });
                    });

                return true;
            }

            return false;
        },
        validate: function () {
            var $form = $('#co-payment-form');
            return $form.length ? $form.valid() : true;
        },
        afterPlaceOrder: function () {
            // The redirection will be handled automatically by Magento
            // using the URL returned by getOrderPlaceRedirectUrl()
        }
    });
});
