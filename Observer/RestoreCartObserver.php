<?php
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
 * @version     1.0.1
 */
namespace Payrexx\PaymentGateway\Observer;

use Exception;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class RestoreCartObserver implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @codeCoverageIgnore
     */
    public function __construct(\Magento\Checkout\Model\Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @codeCoverageIgnore
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $lastRealOrder = $this->checkoutSession->getLastRealOrder();
            if ($lastRealOrder && $lastRealOrder->getPayment()) {
                $paymentMethod = $lastRealOrder->getPayment()->getMethod();
                $state = $lastRealOrder->getData('state');
                if (stripos($paymentMethod, 'payrexx') !== false) {
                    // restore cart items.
                    if (in_array($state, [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT])) {
                        $this->checkoutSession->restoreQuote();
                    }
                }
            }
        } catch (Exception $e) {
            // Nothing to do.
        }
        return true;
    }
}
