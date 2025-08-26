<?php
/**
 * Payrexx Payment Gateway
 *
 * @author      Payrexx <support@payrexx.com>
 * @copyright   Payrexx AG
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
class PaymentMethodCrypto extends PayrexxBase
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'payrexx_payment_crypto';
}
