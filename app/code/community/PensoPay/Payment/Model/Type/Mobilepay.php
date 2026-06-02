<?php

class PensoPay_Payment_Model_Type_Mobilepay
{
    /**
     * Prepare quote for checkout
     */
    public function initCheckout(): void
    {
        $collectTotals = false;
        $quoteSave = false;

        /**
         * Reset multishipping flag
         */
        if ($this->getQuote()->getIsMultiShipping()) {
            $this->getQuote()->setIsMultiShipping(0);
            $quoteSave = true;
        }

        /**
         *  Reset customer balance
         */
        if ($this->getQuote()->getUseCustomerBalance()) {
            $this->getQuote()->setUseCustomerBalance(false);
            $quoteSave = true;
            $collectTotals = true;
        }
        /**
         *  Reset reward points
         */
        if ($this->getQuote()->getUseRewardPoints()) {
            $this->getQuote()->setUseRewardPoints(false);
            $quoteSave = true;
            $collectTotals = true;
        }

        if ($collectTotals) {
            $this->getQuote()->collectTotals();
        }

        if ($quoteSave) {
            $this->getQuote()->save();
        }

        //Set dummy address to include shipping cost
        $this->getQuote()->getShippingAddress()
             ->setCountryId('DK')
             ->setPostcode('9000');
        $this->getQuote()->getShippingAddress()->collectTotals();
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(1);
        $this->getQuote()->getShippingAddress()->collectShippingRates();

        $this->saveShippingMethod($this->getQuote());
    }

    /**
     * Save payment method on quote
     */
    public function savePayment(Mage_Sales_Model_Quote $quote): void
    {
        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod('pensopay_mobilepay');
        } else {
            $quote->getShippingAddress()->setPaymentMethod('pensopay_mobilepay');
        }

        $data = [];
        $data['method'] = 'pensopay_mobilepay';
        $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                          | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                          | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                          | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                          | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;

        $payment = $quote->getPayment();
        $payment->importData($data);

        $quote->save();
    }

    /**
     * Save shipping method on quote
     */
    public function saveShippingMethod(Mage_Sales_Model_Quote $quote, ?string $shippingMethod = null): void
    {
        if ($shippingMethod === null) {
            $shippingMethod = Mage::getStoreConfig('payment/pensopay_mobilepay/default_shipping_method');
        }

        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);

        if (!$rate) {
            Mage::throwException(Mage::helper('checkout')->__('Invalid shipping method.'));
        }

        $quote->getShippingAddress()
              ->setShippingMethod($shippingMethod)
              ->setCollectShippingRates(1)
              ->collectTotals();

        $quote->save();
    }

    /**
     * Save billing address
     *
     * @return array{error: int, message: mixed}|null
     */
    public function saveBilling(Mage_Sales_Model_Quote $quote, stdClass $request): ?array
    {
        $invoiceAddress = $request->invoice_address;
        $nameParts = explode(' ', $invoiceAddress->name);

        $data = [
            'firstname' => array_shift($nameParts),
            'lastname' => join(' ', $nameParts),
            'email' => $invoiceAddress->email,
            'street' => [
                $invoiceAddress->street . ' ' . $invoiceAddress->house_number . $invoiceAddress->house_extension,
            ],
            'city' => $invoiceAddress->city,
            'postcode' => $invoiceAddress->zip_code,
            'country_id' => $invoiceAddress->country_code,
            'telephone' => $invoiceAddress->phone_number,
        ];

        //Set company if available
        if (isset($invoiceAddress->company_name)) {
            $data['company'] = $invoiceAddress->company_name;
        }

        $address = $quote->getBillingAddress();
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
                    ->setEntityType('customer_address')
                    ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        $addressForm->setEntity($address);

        // emulate request object
        $addressData    = $addressForm->extractData($addressForm->prepareRequest($data));
        $addressErrors  = $addressForm->validateData($addressData);

        if (is_array($addressErrors)) {
            Mage::log(var_export(array_values($addressErrors), true), null, 'qp_mpo_addresserror.log');
        }

        $addressForm->compactData($addressData);

        //unset billing address attributes which were not shown in form
        foreach ($addressForm->getAttributes() as $attribute) {
            if (!isset($data[$attribute->getAttributeCode()])) {
                $address->setData($attribute->getAttributeCode());
            }
        }

        $address->setCustomerAddressId(0);

        // Additional form data, not fetched by extractData (as it fetches only attributes)
        $address->setSaveInAddressBook(0);

        // validate billing address
        if (($validateRes = $address->validate()) !== true) {
            return ['error' => 1, 'message' => $validateRes];
        }

        $address->implodeStreetAddress();

        if (true !== ($result = $this->_validateCustomerData($data, $quote))) {
            return $result;
        }

        if (!$quote->isVirtual()) {
            /**
             * Billing address using otions
             */
            $usingCase = empty($request->shipping_address) ? 1 : 0;

            switch ($usingCase) {
                case 0:
                    $shipping = $quote->getShippingAddress();
                    $shipping->setSameAsBilling(0);
                    $this->saveShipping($quote, $request);
                    break;
                case 1:
                    $billing = clone $address;
                    $billing->unsAddressId()->unsAddressType();
                    $shipping = $quote->getShippingAddress();
                    $shippingMethod = $shipping->getShippingMethod();

                    // Billing address properties that must be always copied to shipping address
                    $requiredBillingAttributes = ['customer_address_id'];

                    // don't reset original shipping data, if it was not changed by customer
                    foreach ($shipping->getData() as $shippingKey => $shippingValue) {
                        if (!is_null($shippingValue) && !is_null($billing->getData($shippingKey))
                            && !isset($data[$shippingKey]) && !in_array($shippingKey, $requiredBillingAttributes)
                        ) {
                            $billing->unsetData($shippingKey);
                        }
                    }
                    $shipping->addData($billing->getData())
                             ->setSameAsBilling(1)
                             ->setSaveInAddressBook(0)
                             ->setShippingMethod($shippingMethod)
                             ->setCollectShippingRates(1);
                    break;
            }
        }

        $quote->save();
        return null;
    }

    /**
     * Save shipping address
     */
    public function saveShipping(Mage_Sales_Model_Quote $quote, stdClass $request): void
    {
        $shippingAddress = $request->shipping_address;
        $invoiceAddress = $request->invoice_address;
        $nameParts = explode(' ', $shippingAddress->name);

        $data = [
            'firstname' => array_shift($nameParts),
            'lastname' => join(' ', $nameParts),
            'email' => $shippingAddress->email,
            'street' => [
                $shippingAddress->street . ' ' . $shippingAddress->house_number . $shippingAddress->house_extension,
            ],
            'city' => $shippingAddress->city,
            'postcode' => $shippingAddress->zip_code,
            'country_id' => $shippingAddress->country_code,
        ];

        //Set telephone
        if (!empty($shippingAddress->phone_number)) {
            $data['telephone'] = $shippingAddress->phone_number;
        } else {
            $data['telephone'] = $invoiceAddress->phone_number;
        }

        //Set company if available
        if (isset($shippingAddress->company_name)) {
            $data['company'] = $shippingAddress->company_name;
        }

        $address = $quote->getShippingAddress();

        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm    = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
                    ->setEntityType('customer_address')
                    ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        $addressForm->setEntity($address);
        // emulate request object
        $addressData    = $addressForm->extractData($addressForm->prepareRequest($data));
        $addressErrors  = $addressForm->validateData($addressData);

        if (is_array($addressErrors)) {
            Mage::log(var_export(array_values($addressErrors), true), null, 'qp_mpo_addresserror.log');
        }

        $addressForm->compactData($addressData);
        // unset shipping address attributes which were not shown in form
        foreach ($addressForm->getAttributes() as $attribute) {
            if (!isset($data[$attribute->getAttributeCode()])) {
                $address->setData($attribute->getAttributeCode());
            }
        }

        $address->setCustomerAddressId(0);
        // Additional form data, not fetched by extractData (as it fetches only attributes)
        $address->setSaveInAddressBook(0);
        $address->setSameAsBilling(0);

        $address->implodeStreetAddress();
        $address->setCollectShippingRates(1);
    }

    /**
     * Validate customer data and set some its data for further usage in quote
     * Will return either true or array with error messages
     *
     * @param array<string, mixed> $data
     * @return true|array{error: int, message: string}
     */
    protected function _validateCustomerData(array $data, Mage_Sales_Model_Quote $quote): true|array
    {
        /** @var Mage_Customer_Model_Form $customerForm */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('checkout_register')
                     ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

        if ($quote->getCustomerId()) {
            $customer = $quote->getCustomer();
            $customerForm->setEntity($customer);
            $customerData = $quote->getCustomer()->getData();
        } else {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer');
            $customerForm->setEntity($customer);
            $customerRequest = $customerForm->prepareRequest($data);
            $customerData = $customerForm->extractData($customerRequest);
        }

        $customerErrors = $customerForm->validateData($customerData);

        if (is_array($customerErrors)) {
            return [
                'error'     => -1,
                'message'   => implode(', ', $customerErrors),
            ];
        }

        if ($quote->getCustomerId()) {
            return true;
        }

        $customerForm->compactData($customerData);

        // spoof customer password for guest
        $password = $customer->generatePassword();
        $customer->setPassword($password);
        $customer->setPasswordConfirmation($password);
        // set NOT LOGGED IN group id explicitly,
        // otherwise copyFieldset('customer_account', 'to_quote') will fill it with default group id value
        $customer->setGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);

        $result = $customer->validate();
        if (true !== $result && is_array($result)) {
            return [
                'error'   => -1,
                'message' => implode(', ', $result),
            ];
        }

        // copy customer/guest email to address
        $quote->getBillingAddress()->setEmail($customer->getEmail());

        // copy customer data to quote
        Mage::helper('core')->copyFieldset('customer_account', 'to_quote', $customer, $quote);

        return true;
    }

    /**
     * Sets cart coupon code from checkout to quote
     */
    protected function _setCartCouponCode(): self
    {
        if ($couponCode = $this->getCheckout()->getCartCouponCode()) {
            $this->getQuote()->setCouponCode($couponCode);
        }
        return $this;
    }

    /**
     * Get frontend checkout session object
     */
    public function getCheckout(): Mage_Checkout_Model_Session
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get quote
     */
    public function getQuote(): Mage_Sales_Model_Quote
    {
        return $this->getCheckout()->getQuote();
    }
}
