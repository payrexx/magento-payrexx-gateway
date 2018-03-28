<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @author SoftSolutions4U <info@softsolutions4u.com>
 */
namespace Payrexx\PaymentGateway\Controller\Payment;

/**
 * class \Payrexx\PaymentGateway\Controller\Payment\Redirect
 * Redirect controller to access from frontend
 */
class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * Return result based on the request
     */
    public function execute()
    {
        $paymentUrl = $this->getPaymentUrl();
        if ($paymentUrl) {
            $this->_redirect($paymentUrl);
        }
    }

    /**
     * Get payrexx payment page url
     */
    public function getPaymentUrl()
    {
        // TO DO: Create Payment Gateway
        return "http://www.payrexx.com";
    }
}
