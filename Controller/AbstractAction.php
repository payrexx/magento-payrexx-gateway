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
namespace Payrexx\PaymentGateway\Controller;

/**
 * AbstractAction class is base class for Payrexx Payment Getaway
 */
abstract class AbstractAction extends \Magento\Framework\App\Action\Action
{
    /**
     * Uses additional_information as storage
     */
    const PAYMENT_GATEWAY_ID = 'payrexx_gateway_id';

    /**
     * Uses additional_information as storage
     */
    const PAYMENT_SECURITY_HASH = 'payrexx_security_hash';

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    public $context;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $configSettings;

    /**
     * @var \Magento\Framework\Logger\Monolog
     */
    public $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     * @param \Payrexx\PaymentGateway\Helper\Checkout            $checkoutHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $configSettings
     * @param \Magento\Framework\Logger\Monolog                  $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Payrexx\PaymentGateway\Helper\Checkout $checkoutHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $configSettings,
        \Magento\Framework\Logger\Monolog $logger
    ) {
        parent::__construct($context);
        $this->context         = $context;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory    = $orderFactory;
        $this->checkoutHelper  = $checkoutHelper;
        $this->configSettings  = $configSettings;
        $this->logger          = $logger;
    }

    /**
     * Get the Payrexx configuration hold for Merchant configuration
     *
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public function getPayrexxConfig()
    {
        return $this->configSettings->getValue(
            'payment/payrexx_payment'
        );
    }

    /**
     * This function is redirect to cart after customer is cancel the payment.
     */
    public function executeCancelAction()
    {
        // Add error message show into user
        $this->context->getMessageManager()->addError(
            __('An error occurred while processing your payment. Please try again later.')
        );
        if ($this->checkoutHelper->cancelCurrentOrder('')) {
            $this->checkoutHelper->restoreQuote();
        }
        $this->redirectToCheckoutCart();
    }

    /**
     * Redirect to cart when and restored the previous selected Item.
     */
    public function redirectToCheckoutCart()
    {
        $this->_redirect('checkout/cart');
    }

    /**
     * Get Order object
     *
     * @param integer $orderId Order id
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderDetailByOrderId($orderId)
    {
        $order = $this->orderFactory
            ->create()
            ->loadByIncrementId($orderId);
        if (!$order || !$order->getId()) {
            return null;
        }
        return $order;
    }

    /**
     * Creates Payrexx Instance from the given credentials
     *
     * @return \Payrexx\Payrexx
     */
    public function getPayrexxInstance()
    {
        $config = $this->getPayrexxConfig();
        return new \Payrexx\Payrexx(
            $config['instance_name'],
            $config['api_secret']
        );
    }
}
