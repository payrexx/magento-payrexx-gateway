<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright©2022 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2022 PAYREXX AG
 * @author      Payrexx <support@payrexx.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 * @version     1.0.0
 */
namespace Payrexx\PaymentGateway\Controller\Payment;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;

/**
 * Class \Payrexx\PaymentGateway\Controller\Payment\Failure
 * The Failure controller is accessing from frontend
 */
class Failure extends \Payrexx\PaymentGateway\Controller\AbstractAction
{
    /**
     * Execute payment failure.
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
        $quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

        $order = $checkoutSession->getLastRealOrder();

        if ($order && $order->getState() == Order::STATE_PENDING_PAYMENT) {
            $this->checkoutHelper->cancelCurrentOrder('Order cancelled by customer');
        }

        // delete gateway if user cancelled the order, prevent to pay later on same gateway.
        $status = $this->getRequest()->getParam('payrexx_status');
        if ($status && $status === 'cancel' && $order->getState() === Order::STATE_CANCELED) {
            $payment   = $order->getPayment();
            $gatewayId = $payment->getAdditionalInformation(
                static::PAYMENT_GATEWAY_ID
            );

            $payrexx = $this->getPayrexxInstance();
            $gateway = ObjectManager::getInstance()->create(
                '\Payrexx\Models\Request\Gateway'
            );
            $gateway->setId($gatewayId);
            try {
                $payrexx->delete($gateway);
            } catch (\Payrexx\PayrexxException $e) {
                // no action
            }
        }

        $quote = $quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $checkoutSession->replaceQuote($quote);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            $this->messageManager->addWarningMessage('Your payrexx payment Failed.');
            return $resultRedirect;
        }
        return $this->_redirect('checkout/onepage/failure');
    }
}
