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
 * class \Payrexx\PaymentGateway\Controller\Payment\Webhook
 * After completed the payment, This class to get the response which is sent
 * from payrexx payment call back.
 */
class Webhook extends \Payrexx\PaymentGateway\Controller\AbstractAction
{
    /**
     * Executes to receive post values from request.
     * The order status has been updated if the payment is successful
     */
    public function execute()
    {
        // Check payment getway response
        $post = $this->getRequest()->getPostValue();
        if (empty($post) || empty($post['transaction'])) {
            return;
        }

        $transaction = $post['transaction'];
        $orderId     = $transaction['invoice']['referenceId'];

        // Check transaction status
        if (!$orderId || $transaction['status'] === 'waiting') {
            return;
        }

        $order = $this->getOrderDetailByOrderId($orderId);
        if (!$order) {
            return;
        }

        $payment   = $order->getPayment();
        $gatewayId = $payment->getAdditionalInformation(
            static::PAYMENT_GATEWAY_ID
        );
        $paymentHash = $payment->getAdditionalInformation(
            static::PAYMENT_SECURITY_HASH
        );

        $isValidHash = $this->isValidHash($post, $paymentHash);
        if (!$isValidHash) {
            // Set the fraud status when payment is frauded.
            $order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD);
            $order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
            $order->save();
        }

        $payrexx = $this->getPayrexxInstance();
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($gatewayId);
        try {
            $response = $payrexx->getOne($gateway);
            $status   = $response->getStatus();
        } catch (\Payrexx\PayrexxException $e) {
            $this->logger->addError(
                'Payrexx Fetch Gateway : ' . json_encode($e->getMessage())
            );
            return;
        }

        if (
            $status !== $transaction['status'] ||
            in_array($transaction['psp'], ['PrePayment', 'Invoice'])
        ) {
            return;
        }

        if ($status === 'confirmed') {
            // Set the complete status when payment is completed.
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->save();
            return;
        }
    }

    /**
     * Check hash value is valid or not
     *
     * @param  array   $transaction Post Values
     * @param  string  $paymentHash Saved hash value
     * @return boolean True if the hash values is equal, false otherwise
     */
    public function isValidHash($transaction, $paymentHash)
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
