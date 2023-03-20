/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2018 PAYREXX AG
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
        'googlePayLibrary'
    ],
    function (
        $,
        ko,
        Component,
        placeOrderAction,
        additionalValidators,
        url
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payrexx_PaymentGateway/payment/payment-method-google-pay',
            },

            deviceSupported: function() {
                alert('Checking device support');
                try {
                    const allowedCardNetworks = [
                        "MASTERCARD", "VISA"
                    ];
                    const allowedCardAuthMethods = ["CRYPTOGRAM_3DS"];
                    const baseCardPaymentMethod = {
                        type: 'CARD',
                        parameters: {
                        allowedAuthMethods: allowedCardAuthMethods,
                        allowedCardNetworks: allowedCardNetworks
                        }
                    };
                    const baseRequest = {
                        apiVersion: 2,
                        apiVersionMinor: 0
                    };
                    // const cardPaymentMethod = Object.assign(
                    //     {
                    //         tokenizationSpecification: tokenizationSpecification
                    //     },
                    //     baseCardPaymentMethod
                    // );
                    const isReadyToPayRequest = Object.assign({}, baseRequest);
                    isReadyToPayRequest.allowedPaymentMethods = [
                        baseCardPaymentMethod
                    ];
                    const paymentsClient = new google.payments.api.PaymentsClient(
                        {
                            environment: 'TEST'
                        }
                    );
                    paymentsClient.isReadyToPay(isReadyToPayRequest)
                    .then(function(response) {
                        alert(response);
                        if (response.result) {
                            // add a Google Pay payment button
                            return true;
                        }
                    })
                    .catch(function(err) {
                        alert('catch error');
                        // show error in developer console for debugging
                        return false;
                    });
                    return true;
                } catch (err) {
                    console.log(err);
                    alert('try catch error');
                    return true;
                }
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
        });
    }
);
