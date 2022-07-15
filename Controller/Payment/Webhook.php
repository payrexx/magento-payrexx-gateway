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
namespace Payrexx\PaymentGateway\Controller\Payment;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;
use Payrexx\Models\Response\Transaction;

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
            throw new \Exception('Payment hash incorrect. Fraud suspect');
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

        $state = '';
        switch ($status) {
            case Transaction::CONFIRMED:
                $state = Order::STATE_PROCESSING;
                break;
            case Transaction::CANCELLED:
            case Transaction::DECLINED:
            case Transaction::ERROR:
            case Transaction::EXPIRED:
                $state = Order::STATE_CANCELED;
                break;
            case Transaction::REFUNDED:
                $state = Order::STATE_CLOSED;
                break;
            case Transaction::WAITING:
                $state = Order::STATE_PENDING_PAYMENT;
                break;
            case Transaction::PARTIALLY_REFUNDED:
                try {
                    $state = self::STATE_PAYREXX_PARTIAL_REFUND;
                    $orderStatusCollection = ObjectManager::getInstance()->create(
                        '\Magento\Sales\Model\ResourceModel\Order\Status\Collection'
                    );
                    $orderStatusCollection = $orderStatusCollection->toOptionArray();
                    $payrexxPartialRefund = array_search($state, array_column($orderStatusCollection, 'value'));
                    if (!$payrexxPartialRefund) { // if custom order status does not exit.
                        $state = Order::STATE_CLOSED;
                    }
                } catch (\Exception $e) {
                    $state = Order::STATE_CLOSED;
                }
                break;
        }
        if (empty($state)) {
            return;
        }
        if ($this->isAllowedToChangeState($order->getState(), $state)) {
            $order->setState($state);
            $order->setStatus($state);
            $order->save();
            $history = $order->addCommentToStatusHistory(
                'Status updated by Payrexx Webhook'
            );
            $history->save();
        }
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

    /**
     * Check the transition is allowed or not
     *
     * @param string $oldState
     * @param string $newState
     * @return bool
     */
    private function isAllowedToChangeState($oldState, $newState)
    {
        switch ($oldState) {
            case Order::STATE_PENDING_PAYMENT:
                return in_array($newState, [
                    Order::STATE_PROCESSING,
                    Order::STATE_CLOSED,
                    Order::STATE_CANCELED,
                ]);
            case Order::STATE_PROCESSING:
            case Order::STATE_COMPLETE:
                return in_array($newState, [
                    Order::STATE_CLOSED,
                    self::STATE_PAYREXX_PARTIAL_REFUND,
                ]);
            case Order::STATE_CLOSED:
                return false;
            case Order::STATE_CANCELED:
                return in_array($newState, [
                    Order::STATE_PROCESSING,
                    Order::STATE_PENDING_PAYMENT
                ]);
            case self::STATE_PAYREXX_PARTIAL_REFUND:
                return in_array($newState, [
                    Order::STATE_CLOSED,
                ]);
        }
        return false;
    }
}
