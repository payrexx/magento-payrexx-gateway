/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2023 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2023 PAYREXX AG
 * @author      Payrexx <support@payrexx.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 * @version     1.0.0
 */

/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'googlePayLibraryJs',
    ],
    function (
        $,
        ko,
        Component,
        placeOrderAction,
        additionalValidators,
        url,
        googlePayJs
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payrexx_PaymentGateway/payment/payment-method',
            },

            /**
             * Get payment method data
             *
             * @returns {object}
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {}
                };
            },
            /**
             * Place order
             *
             * @param {object} data  jQuery Ui class
             * @param {object} event jQuery event
             * @returns {Boolean}
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    $.when(
                        placeOrderAction(this.getData(), this.messageContainer)
                    )
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                            function () {
                                self.afterPlaceOrder();
                            }
                        );

                    return true;
                }
                return false;
            },

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                // Redirect into controller
                $.mage.redirect(
                    url.build('payrexx/payment/redirect')
                );
            },

            /**
             * Check the device support google pay or not.
             *
             * @returns bool
             */
            deviceSupported: function() {
                try {
                    const baseRequest = {
                        apiVersion: 2,
                        apiVersionMinor: 0
                    };
                    const allowedCardNetworks = ['MASTERCARD', 'VISA'];
                    const allowedCardAuthMethods = ['CRYPTOGRAM_3DS'];
                    const baseCardPaymentMethod = {
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: allowedCardAuthMethods,
                            allowedCardNetworks: allowedCardNetworks
                        }
                    };

                    const isReadyToPayRequest = Object.assign({}, baseRequest);
                    isReadyToPayRequest.allowedPaymentMethods = [
                        baseCardPaymentMethod
                    ];
                    const paymentsClient = new google.payments.api.PaymentsClient(
                        {
                            environment: 'TEST'
                        }
                    );
                    paymentsClient.isReadyToPay(isReadyToPayRequest).then(function(response) {
                        if (response.result) {
                            console.log("Payrexx Google Pay supported on this device/browser");
                            jQuery("#payrexx_payment_google_pay").parent().parent('.payment-method').show();
                        } else {
                            console.warn("Payrexx Google Pay is not supported on this device/browser");
                        }
                    }).catch(function(err) {
                        return false;
                    });
                } catch (err) {
                    return false;
                }
            },

            /**
             * Returns payment method logo.
             *
             * @return string
             */
            getPaymentMethodImage: function() {
                return require.toUrl('Payrexx_PaymentGateway/images/cardicons/card_google_pay.svg');
            },
        });
    }
);
