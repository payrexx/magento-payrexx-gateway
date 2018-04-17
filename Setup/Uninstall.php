<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2018 PAYREXX AG
 * @author      SoftSolutions4U <info@softsolutions4u.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 * @version     1.0.0
 */
namespace Payrexx\PaymentGateway\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Remove the data during module uninstall
 */
class Uninstall implements UninstallInterface
{
    /**
     * Remove data that was created during module installation.
     *
     * @param SchemaSetupInterface   $setup   DataBase schema resource interface
     * @param ModuleContextInterface $context Context of a module interface
     */
    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        // Remove config values
        $configTable = $setup->getTable('core_config_data');
        $setup->getConnection()->delete(
            $configTable,
            "`path` LIKE 'payment/payrexx_payment/%'"
        );
        $setup->endSetup();
    }
}
