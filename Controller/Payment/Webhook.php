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

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;
use Payrexx\Models\Response\Transaction;
use Magento\Sales\Helper\Data as SalesData;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction as MagentoTransaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

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
        try {
            $data = $this->getRequest()->getPostValue();
            $this->processWebhook($data);
        } catch(Exception $e) {
            $this->sendResponse('Error: ' . $e->getMessage());
        }
    }

    /**
     * Process webhook data
     *
     * @param array $data
     */
    private function processWebhook($data)
    {
        $requestTransaction = $data['transaction'] ?? false;
        $requestTransactionStatus = $requestTransaction['status'] ?? false;
        $orderId = $requestTransaction['invoice']['referenceId'] ?? false;

        if (!$requestTransaction || !$requestTransactionStatus || !$orderId) {
            $this->sendResponse('Error: Payrexx Webhook Data incomplete');
        }

        $order = $this->getOrderDetailByOrderId($orderId);
        if (!$order) {
            $this->sendResponse('Error: No order found with ID ' . $orderId);
        }

        $payment   = $order->getPayment();
        $gatewayId = $payment->getAdditionalInformation(
            static::PAYMENT_GATEWAY_ID
        );

        try {
            $payrexx = $this->getPayrexxInstance();
            $gateway = ObjectManager::getInstance()->create(
                '\Payrexx\Models\Request\Gateway'
            );
            $gateway->setId($gatewayId);

            $response = $payrexx->getOne($gateway);
            $status   = $response->getStatus();
        } catch (\Payrexx\PayrexxException $e) {
            $this->sendResponse('Error: No Payrexx Gateway found with ID: ' . $gatewayId);
        }

        if ($status !== $requestTransactionStatus) {
            $this->sendResponse('Error: Fraudulent transaction status');
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
            $this->sendResponse('Error: ' . $status . ' case not implemented');
        }
        if (!$this->isAllowedToChangeState($order->getState(), $state)) {
            $this->sendResponse(
                'Error: Process not allowed. Order state in magento: ' . $order->getState()
            );
        }

        $order->setState($state);
        $order->setStatus($state);
        $order->save();
        $history = $order->addCommentToStatusHistory(
            'Status updated by Payrexx Webhook'
        );
        $history->save();

        if ($state === Order::STATE_PROCESSING && $order->canInvoice()) {
            $this->createInvoice($order);
        }
        $this->sendResponse('Success: Webhook processed successfully!');
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

    /**
     * Create Invoice
     *
     * @param Order $order
     */
    private function createInvoice($order)
    {
        $salesData = \Magento\Framework\App\ObjectManager::getInstance()->get(SalesData::class);
        $invoiceService = ObjectManager::getInstance()->create(InvoiceService::class);
        $transaction = ObjectManager::getInstance()->create(MagentoTransaction::class);

        // prepare invoice
        $invoice = $invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->save();

        $transactionSave = $transaction->addObject($invoice)->addObject($invoice->getOrder());
        $transactionSave->save();

        // ToDo: Decide whether the invoice should be sent out or not and adapt code accordingly
        // if ($salesData->canSendNewInvoiceEmail()) {
        //     $invoiceSender = ObjectManager::getInstance()->create(InvoiceSender::class);
        //     $invoiceSender->send($invoice);
        // }
        $order->addCommentToStatusHistory(
            __('Notified customer about invoice creation #%1.', $invoice->getId())
        )->setIsCustomerNotified(true)->save();
    }

    /**
     * Returns webhook response
     *
     * @param string     $message      success or error message
     * @param array      $data         response data
     * @param string|int $responseCode response code
     */
    private function sendResponse($message, $data = array(), $responseCode = 200)
    {
        $response['message'] = $message;
        if (!empty($data)) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        http_response_code($responseCode);
        die();
    }
}
