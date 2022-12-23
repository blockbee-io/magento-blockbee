define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Blockbee_Blockbee/js/model/validate-cryptocurrency'
    ],
    function (Component, additionalValidators, validateCryptocurrency) {
        'use strict';
        additionalValidators.registerValidator(validateCryptocurrency);
        return Component.extend({});
    }
);
