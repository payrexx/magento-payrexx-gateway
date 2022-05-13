<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright©2022 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @copyright   2022 PAYREXX AG
 * @author      Payrexx <support@payrexx.com>
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 * @version     1.0.1
 */

namespace Payrexx\PaymentGateway\Setup;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * Custom Order-State code
     */
    const ORDER_STATE_CUSTOM_CODE = 'payrexx_partial_refund';

    /**
     * Custom Order-Status code
     */
    const ORDER_STATUS_CUSTOM_CODE = 'payrexx_partial_refund';

    /**
     * Custom Order-Status label
     */
    const ORDER_STATUS_CUSTOM_LABEL = 'Payrexx Partial Refund';

    /**
     * Status Factory
     *
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * Status Resource Factory
     *
     * @var StatusResourceFactory
     */
    protected $statusResourceFactory;
     /**
     *
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     */
    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    /**
     * upgrade the module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->createNewOrderStatuses();
        }
    }

    /**
     * Create new custom order status and states
     *
     * @return void
     */
    protected function createNewOrderStatuses()
    {
        $statusData = [
            [
                'status' => self::ORDER_STATUS_CUSTOM_CODE,
                'label' => self::ORDER_STATUS_CUSTOM_LABEL,
                'state' => self::ORDER_STATE_CUSTOM_CODE,
            ]
        ];
        foreach ($statusData as $payrexxStatus) {
            /** @var StatusResource $statusResource */
            $statusResource = $this->statusResourceFactory->create();
            /** @var Status $status */
            $status = $this->statusFactory->create();
            $status->setData([
                'status' => $payrexxStatus['status'],
                'label' => $payrexxStatus['label'],
            ]);

            try {
                $statusResource->save($status);
                $status->assignState($payrexxStatus['state'], true, true);
            } catch (AlreadyExistsException $exception) {
                return;
            }
        }
    }
}
