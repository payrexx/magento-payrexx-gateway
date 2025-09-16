<?php
/**
 * Payrexx Payment Gateway
 *
 * @author      Payrexx <support@payrexx.com>
 * @copyright   Payrexx AG
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Payrexx_PaymentGateway',
    __DIR__
);
