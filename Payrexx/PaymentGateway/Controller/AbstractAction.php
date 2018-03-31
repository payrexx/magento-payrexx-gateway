<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @author SoftSolutions4U <info@softsolutions4u.com>
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
    const PAYMENT_GATEWAY_ID    = 'payrexx_gateway_id';

    /**
     * Uses additional_information as storage
     */
    const PAYMENT_SECURITY_HASH  = 'payrexx_security_hash';

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $configSettings;

    /**
     * @var \Magento\Framework\Logger\Monolog
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     * @param \Payrexx\PaymentGateway\Helper\Checkout            $checkoutHelper
     * @param \Magento\Directory\Model\CountryFactory            $countryFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $configSettings
     * @param \Magento\Framework\Logger\Monolog                  $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context              $context,
        \Magento\Checkout\Model\Session                    $checkoutSession,
        \Magento\Sales\Model\OrderFactory                  $orderFactory,
        \Payrexx\PaymentGateway\Helper\Checkout            $checkoutHelper,
        \Magento\Directory\Model\CountryFactory            $countryFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $configSettings,
        \Magento\Framework\Logger\Monolog                  $logger
    ) {
        parent::__construct($context);
        $this->context          = $context;
        $this->checkoutSession  = $checkoutSession;
        $this->orderFactory     = $orderFactory;
        $this->checkoutHelper   = $checkoutHelper;
        $this->countryFactory   = $countryFactory;
        $this->configSettings   = $configSettings;
        $this->logger           = $logger;
        $this->registerPayrexxApi();
    }

    /**
     * Load Payrexx Api files
     */
    public function registerPayrexxApi()
    {
        require_once dirname(__DIR__) . '/vendor/autoload.php';
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
        if (!$order->getId()) {
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
