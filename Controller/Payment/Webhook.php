<?php
/**
 * Payrexx Payment Gateway
 *
 * @author      Payrexx <support@payrexx.com>
 * @copyright   Payrexx AG
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
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

        $requestTransaction = $post['transaction'] ?? null;
        $requestTransactionStatus = $requestTransaction['status'] ?? null;
        $orderId = $requestTransaction['invoice']['referenceId'] ?? null;

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
        if (!$this->isValidHash($requestTransaction, $paymentHash, $order->getStoreId())) {
            // Set the fraud status when payment is frauded.
            $order->setState(Order::STATUS_FRAUD);
            $order->setStatus(Order::STATUS_FRAUD);
            $order->save();
            throw new \Exception('Payment hash incorreect. Fraud suspect');
        }

        try {
            $payrexx = $this->getPayrexxInstance($order->getStoreId());
            $gateway = ObjectManager::getInstance()->create(
                '\Payrexx\Models\Request\Gateway'
            );
            $gateway->setId($gatewayId);

            $payrexxGateway = $payrexx->getOne($gateway);
            $invoices = $payrexxGateway->getInvoices();
            $invoice = end($invoices);

            $transactions = $invoice['transactions'];
            $transaction = end($transactions);

            $status = $transaction['status'];
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
        if (!$this->isAllowedToChangeState($order->getState(), $state)) {
            return;
        }
        // Forcing a canceled order to processing lets Magento close it, which is terminal.
        if ($order->isCanceled()
            && in_array($state, [Order::STATE_PROCESSING, Order::STATE_PENDING_PAYMENT], true)
        ) {
            $transactionId = isset($transaction['id']) ? (string) $transaction['id'] : '';
            $noticeKey = $transactionId . '-' . $status;
            $knownNoticeKey = (string) $payment->getAdditionalInformation(
                static::PAYMENT_CANCELED_ORDER_NOTICE
            );
            if ($transactionId !== '' && $noticeKey === $knownNoticeKey) {
                return;
            }

            $this->logger->warning(
                'Payrexx Webhook: ' . $status . ' transaction received for the canceled order '
                . $order->getIncrementId() . '. Gateway ID: ' . $gatewayId
                . ', transaction ID: ' . $transactionId
            );

            if ($transactionId !== '') {
                $payment->setAdditionalInformation(
                    static::PAYMENT_TRANSACTION_ID,
                    $transactionId
                );
                $payment->setAdditionalInformation(
                    static::PAYMENT_CANCELED_ORDER_NOTICE,
                    $noticeKey
                );
            }
            if (!empty($transaction['uuid'])) {
                $payment->setAdditionalInformation(
                    static::PAYMENT_TRANSACTION_UUID,
                    $transaction['uuid']
                );
            }
            $payment->save();

            if ($state === Order::STATE_PROCESSING) {
                $comment = 'Payrexx: payment confirmed for an already canceled order. The order was '
                    . 'left canceled - please refund the payment in Payrexx or create a new order '
                    . 'manually.';
            } else {
                $comment = 'Payrexx: waiting payment update received for an already canceled order. '
                    . 'The order was left canceled.';
            }

            if ($transactionId !== '') {
                $comment .= ' Transaction ID: ' . $transactionId . '.';
            }

            $order->addCommentToStatusHistory($comment);
            $order->save();
            return;
        }
        if ($state === Order::STATE_CANCELED && $order->canCancel()) {
            $order->registerCancellation('Order was canceled via Payrexx webhook')->save();
        } else {
            $order->setState($state);
            $order->setStatus($state);
            $order->addCommentToStatusHistory('Order Status updated via Payrexx Webhook');
            $order->save();
        }

        // Create Invoice
        $magentoInvoice = null;
        if ($state === Order::STATE_PROCESSING && $order->canInvoice()) {
            $invoiceService = ObjectManager::getInstance()->create(
                '\Magento\Sales\Model\Service\InvoiceService'
            );
            $transaction = ObjectManager::getInstance()->create(
                '\Magento\Framework\DB\Transaction'
            );
            $invoice = $invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->pay();
            $invoice->save();

            $transactionSave = $transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();
            $magentoInvoice = $invoice;
        }

        // Send order confirmation mail
        if ($state === Order::STATE_PROCESSING && !$order->getEmailSent()) {
            $order->setCanSendNewEmailFlag(true);
            $order->save();
            $this->orderSender->send($order, true);
        }

        // Send invoice email
        if (
            $magentoInvoice
            && $state === Order::STATE_PROCESSING
            && !$magentoInvoice->getEmailSent()
        ) {
            $invoiceSender = ObjectManager::getInstance()->create(
                '\Magento\Sales\Model\Order\Email\Sender\InvoiceSender'
            );
            try {
                $invoiceSender->send($magentoInvoice);
                $magentoInvoice->setEmailSent(true);
                $magentoInvoice->save();
            } catch (\Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }
    }

    /**
     * Check hash value is valid or not
     *
     * @param  array   $transaction Post Values
     * @param  string  $paymentHash Saved hash value
     * @param  int     $storeId
     * @return boolean True if the hash values is equal, false otherwise
     */
    private function isValidHash($transaction, $paymentHash, $storeId)
    {
        $postHash = $transaction['invoice']['paymentLink']['hash'];
        $config   = $this->getPayrexxConfig($storeId);
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
