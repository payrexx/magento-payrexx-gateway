<?php
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
namespace Payrexx\PaymentGateway\Controller\Payment;

use Magento\Framework\App\ObjectManager;

/**
 * class \Payrexx\PaymentGateway\Controller\Payment\Webhook
 * After completed the payment, This class to get the response which is sent
 * from payrexx payment call back.
 */
class Webhook extends \Payrexx\PaymentGateway\Controller\AbstractAction
{

    const STATE_PAYREXX_PARTIAL_REFUND = 'payrexx_partial_refund';

    /**
     * Executes to receive post values from request.
     * The order status has been updated if the payment is successful
     */
    public function execute()
    {
        // Check payment getway response
        $post = $this->getRequest()->getPostValue();

        $requestTransaction = $post['transaction'];
        $requestTransactionStatus = $requestTransaction['status'];
        $orderId = $requestTransaction['invoice']['referenceId'];

        if (!$requestTransaction || !$requestTransactionStatus || !$orderId) {
            throw new \Exception('Payrexx Webhook Data incomplete');
        }

        // Nothing todo in case of transaction status waiting
        // if ($requestTransactionStatus === 'waiting') {
        //     return;
        // }

        $order = $this->getOrderDetailByOrderId($orderId);
        if (!$order) {
            throw new \Exception('No order found with ID ' . $orderId);
        }

        $payment   = $order->getPayment();
        $gatewayId = $payment->getAdditionalInformation(
            static::PAYMENT_GATEWAY_ID
        );
        $paymentHash = $payment->getAdditionalInformation(
            static::PAYMENT_SECURITY_HASH
        );
        if (!$this->isValidHash($requestTransaction, $paymentHash)) {
            // Set the fraud status when payment is frauded.
            $order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD);
            $order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
            $order->save();
            throw new \Exception('Payment hash incorreect. Fraud suspect');
        }

        try {
            $payrexx = $this->getPayrexxInstance();
            $gateway = ObjectManager::getInstance()->create(
                '\Payrexx\Models\Request\Gateway'
            );
            $gateway->setId($gatewayId);

            $response = $payrexx->getOne($gateway);
            $status   = $response->getStatus();
        } catch (\Payrexx\PayrexxException $e) {
            throw new \Exception('No Payrexx Gateway found with ID: ' . $gatewayId);
        }

        if ($status !== $requestTransactionStatus) {
            throw new \Exception('Corrupt webhook status');
        }

        switch ($status) {
            case 'confirmed':
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;
            case 'cancelled':
            case 'declined':
            case 'error':
            case 'expired':
                $state = \Magento\Sales\Model\Order::STATE_CANCELED;
                break;
            case 'refunded':
                $state = \Magento\Sales\Model\Order::STATE_CLOSED;
                break;
            case 'waiting':
                $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                break;
            case 'partially-refunded':
                $state = self::STATE_PAYREXX_PARTIAL_REFUND;
                break;
        }
        $order->setState($state);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->save();
        $history = $order->addCommentToStatusHistory(
            'Status updated by Payrexx Webhook'
        );
        $history->save();
    }

    /**
     * Check hash value is valid or not
     *
     * @param  array   $transaction Post Values
     * @param  string  $paymentHash Saved hash value
     * @return boolean True if the hash values is equal, false otherwise
     */
    private function isValidHash($transaction, $paymentHash)
    {
        $postHash = $transaction['invoice']['paymentLink']['hash'];
        $config   = $this->getPayrexxConfig();
        $hash     = hash_hmac('sha1', $postHash, $config['api_secret'], false);
        // Check hash value difference
        if (strcasecmp($hash, $paymentHash) === 0) {
            return true;
        }
        return false;
    }
}
