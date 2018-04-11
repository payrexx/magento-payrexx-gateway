/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @author SoftSolutions4U <info@softsolutions4u.com>
 */

/* @api */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'payrexx_payment',
            component: 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method'
        }
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
