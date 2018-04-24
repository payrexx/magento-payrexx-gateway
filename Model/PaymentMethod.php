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
namespace Payrexx\PaymentGateway\Model;

/**
 * PaymentMethod model for Payrexx
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 *
 * @api
 * @since 100.0.2
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    const PAYMENT_METHOD_PAYREXX_CODE = 'payrexx_payment';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_PAYREXX_CODE;

    /**
     * Run the payment initialize while order place
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Using internal pages for input payment data Can be used in admin
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Can be used in regular checkout
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * @inheritdoc
     */
    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(
            \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
        );
        $stateObject->setStatus(
            \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
        );
        $stateObject->setIsNotified(false);
    }
}
