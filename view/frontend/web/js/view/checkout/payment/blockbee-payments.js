define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'blockbee',
                component: 'Blockbee_Blockbee/js/view/checkout/payment/blockbee'
            }
        );
        return Component.extend({});
    }
);
