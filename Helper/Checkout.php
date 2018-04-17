<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2018 PAYREXX AG
 * @author      SoftSolutions4U <info@softsolutions4u.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 * @version     1.0.0
 */
namespace Payrexx\PaymentGateway\Helper;

/**
 * The Checkout helper help to give the current selected cart item detail
 */
class Checkout
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Cancel last order
     *
     * @param  string  $comment Comment text
     * @return boolean True if order canceled successfully, false otherwise
     */
    public function cancelCurrentOrder($comment)
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (
            $order->getId() &&
            $order->getState() !== \Magento\Sales\Model\Order::STATE_CANCELED
        ) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    /**
     * Restore last active quote
     *
     * @return bool True if quote restored successfully, false otherwise
     */
    public function restoreQuote()
    {
        return $this->checkoutSession->restoreQuote();
    }
}
