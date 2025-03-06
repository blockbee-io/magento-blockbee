define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/js/view/payment/default'
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
            if (event) {
                event.preventDefault();
            }
            this.isPlaceOrderActionAllowed(false);
            this._super();
        },
        validate: function () {
            var $form = jQuery('#co-payment-form');
            return $form.valid && $form.valid();
        },
        afterPlaceOrder: function () {
            // The redirection will be handled automatically by Magento
            // using the URL returned by getOrderPlaceRedirectUrl()
        }
    });
});
