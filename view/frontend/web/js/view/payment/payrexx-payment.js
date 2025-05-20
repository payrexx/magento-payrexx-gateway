/**
 * Payrexx Payment Gateway Module
 *
 * @category  Payrexx
 * @package   Payrexx_PaymentGateway
 * @author    Support <support@payrexx.com>
 * @copyright PAYREXX AG
 */

/* @api */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    var componentJs = 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method';
    var googlePayComponentJs = 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method-google-pay';
    var applePayComponentJs = 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method-apple-pay';
    'use strict';

    rendererList.push(
        {type: 'payrexx_payment', component: componentJs},
        {type: 'payrexx_payment_apple_pay', component: applePayComponentJs},
        {type: 'payrexx_payment_google_pay', component: googlePayComponentJs},
    );
    const payrexxPaymentMethods = [
        'masterpass',
        'mastercard',
        'visa',
        'maestro',
        'jcb',
        'american_express',
        'wirpay',
        'paypal',
        'bitcoin',
        'klarna',
        'billpay',
        'bonus',
        'cashu',
        'cb',
        'diners_club',
        'sepa_direct_debit',
        'discover',
        'elv',
        'ideal',
        'invoice',
        'myone',
        'paysafecard',
        'post_finance_pay',
        'swissbilling',
        'twint',
        'barzahlen',
        'bancontact',
        'giropay',
        'eps',
        'oney',
        'centi',
        'heidipay',
        'bank_transfer',
        'pay_by_bank',
        'powerpay',
        'cembrapay',
    ];
    payrexxPaymentMethods.forEach(pm => {
        rendererList.push({ type: 'payrexx_payment_' + pm, component: componentJs });
    });

    return Component.extend({});
});
