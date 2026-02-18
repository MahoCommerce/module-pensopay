<?php

$installer = $this;
$installer->startSetup();

$_tblPayments = $installer->getTable('pensopay/payments');

$tblPayments = $installer->getConnection()
    ->newTable($_tblPayments)
    ->addColumn('id', Maho\Db\Ddl\Table::TYPE_INTEGER, 11, [
        'nullable' => false,
        'unsigned' => true,
        'primary'  => true,
        'identity' => true
    ], 'Increment ID')
    ->addColumn('reference_id', Maho\Db\Ddl\Table::TYPE_INTEGER, 11, [
        'nullable' => false,
        'unsigned' => true
    ], 'Reference ID')
    ->addColumn('is_virtualterminal', Maho\Db\Ddl\Table::TYPE_BOOLEAN, 11, ['nullable' => false, 'default' => 0], 'Is Payment VirtualTerminal')
    ->addColumn('order_id', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false, 'unsigned' => true], 'Order ID')
    ->addColumn('accepted', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Accepted by provider')
    ->addColumn('currency', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Currency')
    ->addColumn('state', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'State')
    ->addColumn('link', Maho\Db\Ddl\Table::TYPE_TEXT, 65534, ['nullable' => false], 'Payment Link')
    ->addColumn('amount', Maho\Db\Ddl\Table::TYPE_DECIMAL, '12,4', ['nullable' => false], 'Amount')
    ->addColumn('amount_refunded', Maho\Db\Ddl\Table::TYPE_DECIMAL, '12,4', ['nullable' => false], 'Amount Refunded')
    ->addColumn('amount_captured', Maho\Db\Ddl\Table::TYPE_DECIMAL, '12,4', ['nullable' => false], 'Amount Captured')
    ->addColumn('locale_code', Maho\Db\Ddl\Table::TYPE_TEXT, 65534, ['nullable' => false], 'Language')
    ->addColumn('autocapture', Maho\Db\Ddl\Table::TYPE_BOOLEAN, 1, ['nullable' => false], 'Autocapture')

    ->addColumn('customer_name', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Customer Name')
    ->addColumn('customer_email', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Customer Email')
    ->addColumn('customer_street', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Customer Street')
    ->addColumn('customer_zipcode', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Customer Zipcode')
    ->addColumn('customer_city', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Customer City')

    ->addColumn('created_at', Maho\Db\Ddl\Table::TYPE_DATETIME, null, [], 'Created At')
    ->addColumn('updated_at', Maho\Db\Ddl\Table::TYPE_DATETIME, null, [], 'Updated At')
    ->addColumn('operations', Maho\Db\Ddl\Table::TYPE_TEXT, 65534, ['nullable' => false], 'Operations')
    ->addColumn('metadata', Maho\Db\Ddl\Table::TYPE_TEXT, 65534, ['nullable' => false], 'Metadata')
    ->addColumn('fraud_probability', Maho\Db\Ddl\Table::TYPE_VARCHAR, 255, ['nullable' => false], 'Fraud Probability')
    ->addColumn('hash', Maho\Db\Ddl\Table::TYPE_TEXT, 65534, ['nullable' => false], 'Payment Hash')
    ->addIndex(
        $installer->getIdxName('pensopay/payments', ['id'], Maho\Db\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE),
        ['id'], ['type' => Maho\Db\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE])
    ->setComment('PensoPay Virtual Terminal Payments');
$installer->getConnection()->createTable($tblPayments);

$installer->endSetup();
