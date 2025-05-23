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
class PaymentMethodPayByBank extends PayrexxBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'payrexx_payment_pay_by_bank';
}
