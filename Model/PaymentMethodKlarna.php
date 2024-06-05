<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2024 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2024 PAYREXX AG
 * @author      Payrexx <support@payrexx.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
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
class PaymentMethodKlarna extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    const PAYMENT_METHOD_PAYREXX_CODE = 'payrexx_payment_klarna';

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
     * @inheritdoc
     */
    public function initialize($paymentAction, $stateObject)
    {
        if ($paymentAction === static::ACTION_AUTHORIZE) {
            $stateObject->setState(
                \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            );
            $stateObject->setStatus(
                \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            );
            $stateObject->setIsNotified(false);
        }
    }
}
