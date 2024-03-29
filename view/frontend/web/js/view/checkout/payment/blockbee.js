/* @api */
define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'domReady!'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Blockbee_Blockbee/checkout/payment/blockbee'
        },
        getCryptocurrencies: function () {
            return window.checkoutConfig.payment.blockbee.cryptocurrencies;
        },
        getInstructions: function () {
            return window.checkoutConfig.payment.blockbee.instructions;
        },
        getSelectedCoin() {
            return document.getElementById("blockbee_payment_cryptocurrency_id")?.value ? document.getElementById("blockbee_payment_cryptocurrency_id").value : '';
        },
        getData: function () {
            return {
                "method": 'blockbee',
                "additional_data": {
                    "blockbee_coin": this.getSelectedCoin()
                }
            };
        }
    });
});
