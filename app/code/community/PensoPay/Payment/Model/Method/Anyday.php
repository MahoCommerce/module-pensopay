<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

declare(strict_types=1);

class PensoPay_Payment_Model_Method_Anyday extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_anyday';
    protected $_formBlockType = 'pensopay/form_anyday';

    /**
     * Get payment methods
     */
    #[\Override]
    public function getPaymentMethods(): mixed
    {
        return 'anyday-split';
    }

    #[\Override]
    public function canUseForCurrency($currencyCode): bool
    {
        return $currencyCode === 'DKK'; //Anyday-split currently only has DKK available
    }
}
