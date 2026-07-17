<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

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
