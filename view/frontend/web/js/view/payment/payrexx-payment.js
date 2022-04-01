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
    var componentJs = 'Payrexx_PaymentGateway/js/view/payment/method-renderer/payment-method';
    'use strict';

    rendererList.push(
        {type: 'payrexx_payment', component: componentJs},
        {type: 'payrexx_payment_masterpass', component: componentJs},
        {type: 'payrexx_payment_mastercard', component: componentJs},
        {type: 'payrexx_payment_visa', component: componentJs},
        {type: 'payrexx_payment_apple_pay', component: componentJs},
        {type: 'payrexx_payment_maestro', component: componentJs},
        {type: 'payrexx_payment_jcb', component: componentJs},
        {type: 'payrexx_payment_american_express', component: componentJs},
        {type: 'payrexx_payment_wirpay', component: componentJs},
        {type: 'payrexx_payment_paypal', component: componentJs},
        {type: 'payrexx_payment_bitcoin', component: componentJs},
        {type: 'payrexx_payment_sofortueberweisung', component: componentJs},
        {type: 'payrexx_payment_billpay', component: componentJs},
        {type: 'payrexx_payment_bonuscard', component: componentJs},
        {type: 'payrexx_payment_cashu', component: componentJs},
        {type: 'payrexx_payment_cb', component: componentJs},
        {type: 'payrexx_payment_diners_club', component: componentJs},
        {type: 'payrexx_payment_direct_debit', component: componentJs},
        {type: 'payrexx_payment_discover', component: componentJs},
        {type: 'payrexx_payment_elv', component: componentJs},
        {type: 'payrexx_payment_ideal', component: componentJs},
        {type: 'payrexx_payment_invoice', component: componentJs},
        {type: 'payrexx_payment_myone', component: componentJs},
        {type: 'payrexx_payment_paysafecard', component: componentJs},
        {type: 'payrexx_payment_postfinance_card', component: componentJs},
        {type: 'payrexx_payment_postfinance_efinance', component: componentJs},
        {type: 'payrexx_payment_swissbilling', component: componentJs},
        {type: 'payrexx_payment_twint', component: componentJs},
        {type: 'payrexx_payment_barzahlen', component: componentJs},
        {type: 'payrexx_payment_bancontact', component: componentJs},
        {type: 'payrexx_payment_giropay', component: componentJs},
        {type: 'payrexx_payment_eps', component: componentJs},
        {type: 'payrexx_payment_google_pay', component: componentJs},
        {type: 'payrexx_payment_klarna_paynow', component: componentJs},
        {type: 'payrexx_payment_klarna_paylater', component: componentJs},
        {type: 'payrexx_payment_oney', component: componentJs},
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
