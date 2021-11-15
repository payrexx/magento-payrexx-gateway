<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2021 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2021 PAYREXX AG
 * @author      Payrexx <support@payrexx.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 * @version     2.0.0
 */
namespace Payrexx\PaymentGateway\Controller\Payment;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * class \Payrexx\PaymentGateway\Controller\Payment\Webhook
 * After completed the payment, This class to get the response which is sent
 * from payrexx payment call back.
 * 
 * The webhook supports to magento version > 2.2
 */
class WebhookV2 extends \Payrexx\PaymentGateway\Controller\AbstractAction implements CsrfAwareActionInterface
{
    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Executes to receive post values from request.
     * The order status has been updated if the payment is successful
     */
    public function execute()
    {
        echo "Hello world!";
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

        $isValidHash = $this->isValidHash($transaction, $paymentHash);
        if (!$isValidHash) {
            // Set the fraud status when payment is frauded.
            $order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD);
            $order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
            $order->save();
        }

        $payrexx = $this->getPayrexxInstance();
        $gateway = ObjectManager::getInstance()->create(
            '\Payrexx\Models\Request\Gateway'
        );
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

        if ($status !== $transaction['status'] ||
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
