/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2022 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2022 PAYREXX AG
 * @author      Payrexx <support@payrexx.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 * @version     1.0.0
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
        },
        {
            type: 'payrexx_payment_mastercard',
            component: 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method'
        },
        {
            type: 'payrexx_payment_visa',
            component: 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method'
        },
        {
            type: 'payrexx_payment_apple_pay',
            component: 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method'
        },
        {
            type: 'payrexx_payment_maestro',
            component: 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method'
        },
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
