/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @author SoftSolutions4U <info@softsolutions4u.com>
 */

/*browser:true*/
/*global define*/
define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default'
],
function (
    $,
    ko,
    Component
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Payrexx_PaymentGateway/payment/payment-method'
        },

        getData: function() {
            return {
                'method': this.item.method,
                'additional_data': {}
            };
        }
    });
});
