<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

declare(strict_types=1);

class PensoPay_Payment_Model_Method_Dankort extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_dankort';
    protected $_formBlockType = 'pensopay/form_dankort';

    /**
     * Get payment methods
     */
    #[\Override]
    public function getPaymentMethods(): mixed
    {
        return 'dankort';
    }
}
