define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
    ],
    function (Component, additionalValidators, validateCryptocurrency) {
        'use strict';
        additionalValidators.registerValidator(validateCryptocurrency);
        return Component.extend({});
    }
);
