<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 09.05.18
 * Time: 16:21
 */

namespace Netzexpert\PaymentMethodMap\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Psr\Log\LoggerInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\DB\FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * @var \Magento\Framework\DB\Select\QueryModifierFactory
     */
    private $queryModifierFactory;

    /**
     * @var \Magento\Framework\DB\Query\Generator
     */
    private $queryGenerator;

    /** @var LoggerInterface  */
    private $logger;

    /**
     * UpgradeData constructor.
     * @param \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory
     * @param \Magento\Framework\DB\Select\QueryModifierFactory $queryModifierFactory
     * @param \Magento\Framework\DB\Query\Generator $queryGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory,
        \Magento\Framework\DB\Select\QueryModifierFactory $queryModifierFactory,
        \Magento\Framework\DB\Query\Generator $queryGenerator,
        LoggerInterface $logger
    ) {
        $this->fieldDataConverterFactory    = $fieldDataConverterFactory;
        $this->queryModifierFactory         = $queryModifierFactory;
        $this->queryGenerator               = $queryGenerator;
        $this->logger                       = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $this->convertSerializedDataToJson($setup);
        }
    }

    /**
     * Upgrade to version 2.0.1, convert data for the core_config_data
     * from serialized to JSON format
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function convertSerializedDataToJson(ModuleDataSetupInterface $setup)
    {
        $fieldDataConverter = $this->fieldDataConverterFactory->create(
            \Magento\Framework\DB\DataConverter\SerializedToJson::class
        );
        // Convert data for the option with static name in quote_item_option.value
        $queryModifier = $this->queryModifierFactory->create(
            'in',
            [
                'values' => [
                    'path' => [
                        'shipping/paymentmethodmap/map'
                    ]
                ]
            ]
        );
        try {
            $fieldDataConverter->convert(
                $setup->getConnection(),
                $setup->getTable('core_config_data'),
                'config_id',
                'value',
                $queryModifier
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
