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
