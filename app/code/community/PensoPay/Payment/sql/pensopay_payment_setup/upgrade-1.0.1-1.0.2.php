<?php

/** @var Mage_Core_Model_Resource_Setup $this */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('pensopay/payments'), 'acquirer', [
    'type' => Maho\Db\Ddl\Table::TYPE_TEXT,
    'length' => 50,
    'comment' => 'Acquirer',
    'nullable' => false,
]);

$installer->endSetup();
