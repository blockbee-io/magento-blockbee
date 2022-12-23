require([
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals-processor/default'
], function ($, url, quote, totalsDefaultProvider) {
    'use strict';
    $.getJSON(url.build('blockbee/index/cartquote') + '?selected=', function (data) {
        totalsDefaultProvider.estimateTotals(quote.shippingAddress());
    });

    $('body').on('change', function () {

        var cryptoSelector = $('#blockbee_payment_cryptocurrency_id');

        var linkUrl = url.build('blockbee/index/cartquote');

        var feeContainer = $('.totals.fee.excl');

        setInterval(function () {
            if ($('body').attr('aria-busy') === 'false') {
                if (quote.paymentMethod?._latestValue.method === 'blockbee' && parseFloat($('.totals.fee.excl .price').html().replace(/\D/g, '')) > 0) {
                    feeContainer.show();
                } else {
                    feeContainer.hide();
                }
            }
        }, 1000);

        cryptoSelector.unbind('change');
        cryptoSelector.on('change', function () {
            $.getJSON(linkUrl + '?selected=' + cryptoSelector.val(), function (data) {
                totalsDefaultProvider.estimateTotals(quote.shippingAddress());
            });
        });
    });
});
