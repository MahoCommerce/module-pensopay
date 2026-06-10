<?php

class PensoPay_Payment_Model_Api
{
    protected string $baseurl = 'https://api.quickpay.net';

    /**
     * @throws Mage_Core_Exception
     */
    protected function _setupRequest(\Maho\DataObject $request, Mage_Sales_Model_Order $order): void
    {
        $request->setOrderId($order->getIncrementId());
        $request->setCurrency($order->getOrderCurrencyCode());

        /** @var PensoPay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pensopay');
        $helper->setTransactionStoreId((int) $order->getStore()->getId());

        if ($textOnStatement = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_TEXT_ON_STATEMENT, $order->getStore())) {
            $request->setTextOnStatement($textOnStatement);
        }

        $request->setVariables([
            'order_id' => $order->getId(),
        ]);

        if (!$order->getIsVirtualTerminal()) {
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            $orderPayment = $order->getPayment();

            if ($orderPayment && $orderPayment->getMethodInstance()->getPaymentMethods() === 'mobilepay') {
                $billingAddress = null;
                $shippingAddress = null;
            }

            if ($order->getIsVirtual()) {
                //Re-use billing address as shipping address
                $shippingAddress = $billingAddress;
            }

            //Add billing address
            if ($billingAddress) {
                $address = [];

                $address['name'] = $billingAddress->getName();
                $address['street'] = $billingAddress->getStreetFull();
                $address['city'] = $billingAddress->getCity();
                $address['zip_code'] = $billingAddress->getPostcode();
                $address['region'] = $billingAddress->getRegion();
                $address['country_code'] = Mage::app()->getLocale()->getTranslation($billingAddress->getCountryId(), 'Alpha3ToTerritory');
                $address['phone_number'] = $billingAddress->getTelephone();
                $address['email'] = $billingAddress->getEmail();

                $request->setInvoiceAddress($address);
            }

            //Add shipping_address
            if ($shippingAddress) {
                $address = [];

                $address['name'] = $shippingAddress->getName();
                $address['street'] = $shippingAddress->getStreetFull();
                $address['city'] = $shippingAddress->getCity();
                $address['zip_code'] = $shippingAddress->getPostcode();
                $address['region'] = $shippingAddress->getRegion();
                $address['country_code'] = Mage::app()->getLocale()->getTranslation($shippingAddress->getCountryId(), 'Alpha3ToTerritory');
                $address['phone_number'] = $shippingAddress->getTelephone();
                $address['email'] = $shippingAddress->getEmail();

                $request->setShippingAddress($address);
            }

            $basket = [];

            //order is arbitrary

            //Add order items to basket array
            /** @var Mage_Sales_Model_Order_Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                $product = [
                    'qty'        => (int) $item->getQtyOrdered(),
                    'item_no'    => $item->getSku(),
                    'item_name'  => $item->getName(),
                    'item_price' => (int) (($item->getPriceInclTax() - $item->getDiscountAmount()) * 100),
                    'vat_rate'   => $item->getTaxPercent() / 100,
                ];

                $basket[] = $product;
            }

            //Set shipping information
            $shipping = [];

            $shipping['method'] = 'pick_up_point';
            if ($order->getCustomShippingCode()) {
                $shipping['method'] = $order->getCustomShippingCode();
            }

            $shipping['amount'] = (int) ($order->getShippingInclTax() * 100);

            $request->setShipping($shipping);
        } else { //Order is from virtual terminal
            $basket = [
                [
                    'qty'        => 1,
                    'item_no'    => 'virtualterminal',
                    'item_name'  => 'Products',
                    'item_price' => $order->getGrandTotal(),
                    'vat_rate'   => 0.25, //TODO
                ],
            ];
        }

        $request->setBasket($basket);
    }

    /**
     * Create payment for order
     *
     * @throws Mage_Core_Exception
     */
    public function createPayment(Mage_Sales_Model_Order $order): mixed
    {
        $request = new \Maho\DataObject();

        $this->_setupRequest($request, $order);

        Mage::dispatchEvent('pensopay_create_payment_before', ['request' => $request]);

        //Create payment via API
        $payment = $this->request('payments', $request->toArray());

        //Mage::log(var_export($payment, true), null, 'request.log');

        return Mage::helper('core')->jsonDecode($payment, false);
    }

    /**
     * Update payment
     *
     * @throws Mage_Core_Exception
     */
    public function updatePayment(Mage_Sales_Model_Order $order): mixed
    {
        $request = new \Maho\DataObject();

        $this->_setupRequest($request, $order);

        if ($order->getIsVirtualTerminal()) {
            $request->setId($order->getReferenceId());
        } //TODO: If order is not virtual terminal, build logic for getting the payment(s?) from the order to update

        Mage::dispatchEvent('pensopay_update_payment_before', ['request' => $request]);

        //Update payment via API
        $endpoint = sprintf('payments/%s', $order->getReferenceId());
        $payment = $this->request($endpoint, $request->toArray(), 'PATCH', [200]);

        return Mage::helper('core')->jsonDecode($payment, false);
    }

    public function cancel(string $paymentId): mixed
    {
        $request = new \Maho\DataObject();
        $request->setId($paymentId);

        Mage::dispatchEvent('pensopay_cancel_payment_before', ['request' => $request]);

        //Update payment via API
        $endpoint = sprintf('payments/%s/cancel?synchronized', $paymentId);
        $payment = $this->request($endpoint, $request->toArray(), 'POST', [200, 202]);

        return Mage::helper('core')->jsonDecode($payment, false);
    }

    public function refund(string $paymentId, float $amount): mixed
    {
        $request = new \Maho\DataObject();
        $request->setId($paymentId);
        $request->setAmount($amount * 100);

        Mage::dispatchEvent('pensopay_refund_payment_before', ['request' => $request]);

        //Update payment via API
        $endpoint = sprintf('payments/%s/refund?synchronized', $paymentId);
        $payment = $this->request($endpoint, $request->toArray(), 'POST');

        return Mage::helper('core')->jsonDecode($payment, false);
    }

    /**
     * Create payment link
     *
     * @throws Mage_Core_Exception
     */
    public function createPaymentLink(Mage_Sales_Model_Order $order, string $paymentId, bool $address = false): mixed
    {
        Mage::log($paymentId, null, PensoPay_Payment_Helper_Data::LOG_FILENAME);

        $request = new \Maho\DataObject();
        $request->setAgreementId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AGREEMENT_ID, $order->getStore()));

        if ($order->getIsVirtualTerminal()) {
            $store = array_values(Mage::app()->getStores())[0]; //First non-admin store
        } else {
            $store = $order->getStore();
        }

        if (!$order->getIsVirtualTerminal()) {
            $request->setAmount($order->getTotalDue() * 100);
            if (!$order->getNoRedirects()) {
                $request->setContinueurl($this->getContinueUrl($order->getStore(), (string) $order->getId()));
                $request->setCancelurl($this->getCancelUrl($order->getStore()));
            } else {
                $request->setCancelurl($this->getCancelIframeUrl($order->getStore())); //Even if iframe, we need this to poll payment status
            }
            $request->setLanguage($this->getLanguageFromLocale(Mage::app()->getLocale()->getLocaleCode()));
            $request->setAutocapture(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AUTO_CAPTURE, $store));
            $request->setAutofee(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AUTO_FEE, $store));
            $orderPayment = $order->getPayment();
            if ($orderPayment) {
                $request->setPaymentMethods($orderPayment->getMethodInstance()->getPaymentMethods());
            }

            if ($address) {
                $request->setData('invoice_address_selection', true);
                $request->setData('shipping_address_selection', true);
            }


        } else { //Virtual Terminal order
            $request->setAmount($order->getGrandTotal() * 100);
            $request->setLanguage($this->getLanguageFromLocale($order->getLocaleCode()));
            $request->setAutocapture($order->getAutocapture());
            $request->setAutofee($order->getAutofee());
            $request->setPaymentMethods(Mage::getModel('pensopay/method')->getPaymentMethods());
        }

        if (Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_BRANDING, $store)) {
            $request->setBrandingId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_BRANDING, $store));
        }

        if (Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_ANALYTICS_TRACKING, $store)) {
            $request->setGoogleAnalyticsTrackingId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_ANALYTICS_TRACKING, $store));
        }

        if (Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_ANALYTICS_CLIENT_ID, $store)) {
            $request->setGoogleAnalyticsClientId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_ANALYTICS_CLIENT_ID, $store));
        }


        /** @var PensoPay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pensopay');
        $helper->setTransactionStoreId((int) $store->getId());

        $request->setCallbackUrl($this->getCallbackUrl($store));

        $request->setCustomerEmail($order->getCustomerEmail() ?: '');

        /** @var PensoPay_Payment_Helper_Checkout $pensopayCheckoutHelper */
        $pensopayCheckoutHelper = Mage::helper('pensopay/checkout');

        if ($pensopayCheckoutHelper->isCheckoutIframe() && !$order->getIsVirtualTerminal()) {
            $request->setFramed(true);
        }

        $endpoint = sprintf('payments/%s/link', $paymentId);
        $link = $this->request($endpoint, $request->toArray(), 'PUT');

        Mage::log(var_export($link, true), null, 'request.log');
        return Mage::helper('core')->jsonDecode($link, false)->url;
    }

    /**
     * Request the deletion of the link for a specific payment.
     *
     * @throws Mage_Core_Exception
     */
    public function deletePaymentLink(string $paymentId): mixed
    {
        Mage::log('Deleting payment link for ' . $paymentId, null, PensoPay_Payment_Helper_Data::LOG_FILENAME);

        $request = new \Maho\DataObject();
        $request->setAgreementId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AGREEMENT_ID));

        $endpoint = sprintf('payments/%s/link', $paymentId);
        $link = $this->request($endpoint, $request->toArray(), 'DELETE', [204]); //No content returned for this

        return Mage::helper('core')->jsonDecode($link, false)->url;
    }

    public function capture(string $paymentId, float $amount): mixed
    {
        $request = new \Maho\DataObject();
        $request->setId($paymentId);
        $request->setAmount($amount * 100);

        Mage::dispatchEvent('pensopay_capture_payment_before', ['request' => $request]);

        //Update payment via API
        $endpoint = sprintf('payments/%s/capture?synchronized', $paymentId);
        $payment = $this->request($endpoint, $request->toArray(), 'POST');

        return Mage::helper('core')->jsonDecode($payment, false);
    }

    public function getPayment(string $paymentId, ?Mage_Core_Model_Store $store = null): mixed
    {
        Mage::log('Updating payment state for ' . $paymentId, null, PensoPay_Payment_Helper_Data::LOG_FILENAME);

        $request = new \Maho\DataObject();
        $request->setAgreementId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AGREEMENT_ID, $store));

        $endpoint = sprintf('payments/%s', $paymentId);
        $payment = $this->request($endpoint, $request->toArray(), 'GET');

        return Mage::helper('core')->jsonDecode($payment, false);
    }

    /**
     * Perform a API request
     *
     * @param array<string, mixed> $data
     * @param array<int> $expectedResponseCodes
     * @throws Mage_Core_Exception
     */
    protected function request(string $resource, array $data = [], string $method = 'POST', array $expectedResponseCodes = [200, 201, 202]): string
    {
        $client = \Symfony\Component\HttpClient\HttpClient::create();
        $url = $this->baseurl . '/' . $resource;
        $body = Mage::helper('core')->jsonEncode($data);

        $response = $client->request($method, $url, [
            'headers' => [
                'Authorization'  => 'Basic ' . base64_encode(':' . $this->getApiKey()),
                'Accept-Version' => 'v10',
                'Accept'         => 'application/json',
                'Content-Type'   => 'application/json',
                'Content-Length' => strlen((string) $body),
            ],
            'body' => $body,
        ]);

        $statusCode = $response->getStatusCode();
        if (!in_array($statusCode, $expectedResponseCodes)) {
            $responseBody = $response->getContent(false);
            Mage::log($responseBody, null, PensoPay_Payment_Helper_Data::LOG_FILENAME);
            Mage::throwException($responseBody);
        }

        return $response->getContent(false);
    }

    /**
     * Get API key
     */
    private function getApiKey(): mixed
    {
        /** @var PensoPay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pensopay');
        $storeId = $helper->getTransactionStoreId();
        return Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_API_KEY, $storeId);
    }

    /**
     * Get language string from locale code
     */
    private function getLanguageFromLocale(string $locale): string
    {
        $languageMap = [
            'nb' => 'no',
            'nn' => 'no',
        ];

        $parts = explode('_', $locale);
        $language = $parts[0];

        return $languageMap[$language] ?? $language;
    }

    /**
     * Get continue url
     */
    private function getContinueUrl(Mage_Core_Model_Store $store, string $orderId = ''): string
    {
        return $store->getUrl('pensopay/payment/success', ['_query' => ['ori' => Mage::getModel('core/encryption')->encrypt($orderId)]]);
    }

    private function getCancelIframeUrl(Mage_Core_Model_Store $store): string
    {
        return $store->getUrl('pensopay/payment/iframeCancel');
    }

    /**
     * Get cancel url
     */
    private function getCancelUrl(Mage_Core_Model_Store $store): string
    {
        return $store->getUrl('pensopay/payment/cancel');
    }

    /**
     * Get callback url
     */
    private function getCallbackUrl(Mage_Core_Model_Store $store): string
    {
        return $store->getUrl('pensopay/payment/callback');
    }
}
