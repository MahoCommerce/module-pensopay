<?php

/**
 * System configuration path constants for the module.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

declare(strict_types=1);

class PensoPay_Payment_Model_Config
{
    public const XML_PATH_API_KEY = 'payment/pensopay/api_key';
    public const XML_PATH_PRIVATE_KEY = 'payment/pensopay/private_key';

    public const XML_PATH_TESTMODE_ENABLED = 'payment/pensopay/testmode';
    public const XML_PATH_TEXT_ON_STATEMENT = 'payment/pensopay_payment/text_on_statement';
    public const XML_PATH_AGREEMENT_ID = 'payment/pensopay/agreement_id';
    public const XML_PATH_AUTO_CAPTURE = 'payment/pensopay/auto_capture';
    public const XML_PATH_AUTO_FEE = 'payment/pensopay/auto_fee';
    public const XML_PATH_CHECKOUT_METHOD = 'payment/pensopay/checkout_method';
    public const XML_PATH_ORDER_STATUS_AFTERPAYMENT = 'payment/pensopay/order_status_after_payment';
    public const XML_PATH_ORDER_STATUS_BEFOREPAYMENT = 'payment/pensopay/order_status';
    public const XML_PATH_BRANDING = 'payment/pensopay/brandingid';
    public const XML_PATH_SUBTRACT_STOCK_ON_PROCESSING = 'payment/pensopay/subtract_stock_on_processing';
    public const XML_PATH_ANALYTICS_TRACKING = 'payment/pensopay/googleanalyticstracking';
    public const XML_PATH_ANALYTICS_CLIENT_ID = 'payment/pensopay/googleanalyticsclientid';
}
