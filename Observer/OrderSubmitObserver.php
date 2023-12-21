<?php
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
 */
namespace Payrexx\PaymentGateway\Observer;
 
class OrderSubmitObserver
{
    /**
     * Before execute the order submit
     *
     * @param \Magento\Framework\App\Action\Action $subject
     * @param \Magento\Framework\Event\Observer $observer
     * @return array
     */
    public function beforeExecute($subject, $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $paymentMethod = $order->getPayment()->getMethod();
        if (stripos($paymentMethod, 'payrexx') === false) {
            return [$observer];
        }
        $order->setCanSendNewEmailFlag(false);
        return [$observer];
    }
}