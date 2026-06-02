<?php

class PensoPay_Payment_Adminhtml_PensopayController extends Mage_Adminhtml_Controller_Action
{
    protected ?PensoPay_Payment_Model_Payment $_payment = null;

    protected bool $_redirect = true;

    public function indexAction(): void
    {
        $this->_redirectToTerminal();
    }


    /**
     * Show virtual terminal
     */
    public function terminalAction(): void
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    private function _redirectToTerminal(string $error = ''): void
    {
        if (!empty($error)) {
            $this->_getSession()->addError($error);
        }
        $this->_redirect('adminhtml/pensopay/terminal');
    }

    public function editAction(): void
    {
        $id = $this->getRequest()->getParam('id');

        if (!empty($id)) {
            $payment = Mage::getModel('pensopay/payment')->load($id);
            if (!$payment->getId()) {
                $this->_redirectToTerminal($this->__('Payment not found.'));
                return;
            }
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * @param array<string, mixed> $postData
     */
    private function _getOrderObject(array $postData, ?PensoPay_Payment_Model_Payment $payment = null): Mage_Sales_Model_Order
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');

        if ($payment) {
            $order->setId($payment->getOrderId());
            $order->setIncrementId($payment->getOrderId());
            $order->setReferenceId($payment->getReferenceId());
        } else {
            $order->setId($postData['order_id']);
            $order->setIncrementId($postData['order_id']);
        }

        $order->setIsVirtualTerminal(true);

        $order->setGrandTotal($postData['amount']);
        $order->setOrderCurrencyCode($postData['currency_code']);
        $order->setLocaleCode($postData['locale_code']);
        $order->setAutocapture($postData['autocapture']);
        $order->setAutofee($postData['autofee']);

        $order->setCustomerEmail($postData['customer_email']);
        $order->setCustomerName($postData['customer_name']);
        $order->setCustomerStreet($postData['customer_street']);
        $order->setCustomerZipcode($postData['customer_zipcode']);

        return $order;
    }

    private function _updatePaymentLink(bool $sendEmail): bool
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();
        $postData = $request->getPost();

        $incId = $request->getParam('id');
        /** @var PensoPay_Payment_Model_Payment $paymentModel */
        $paymentModel = Mage::getModel('pensopay/payment');
        if (!empty($incId)) { //Existing payment
            $paymentModel->load($incId);
            if (!$paymentModel->getId()) {
                return false;
            }
        }

        $order = $this->_getOrderObject($postData, $paymentModel);

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();
        $postData = $request->getPost();

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        try {
            $payment = $api->updatePayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id);

            $this->_getSession()->setPaymentLink($paymentLink);
            $this->_getSession()->addSuccess($paymentLink);

            $paymentModel->addData($postData);
            $paymentModel->importFromRemotePayment($payment);
            $paymentModel->setLink($paymentLink);
            $paymentModel->save();

            if ($sendEmail) {
                /** @var PensoPay_Payment_Helper_Data $helper */
                $helper = Mage::helper('pensopay');
                $helper->sendEmail($postData['customer_email'], $postData['customer_name'] ?: '', $paymentModel->getAmount(), $paymentModel->getCurrencyCode(), $paymentLink);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        return true;
    }

    private function _createPaymentLink(bool $sendEmail): bool
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();
        $postData = $request->getPost();

        $order = $this->_getOrderObject($postData);

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        try {
            $payment = $api->createPayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id);
            $this->_getSession()->setPaymentLink($paymentLink);
            $this->_getSession()->addSuccess($paymentLink);

            /** @var PensoPay_Payment_Model_Payment $newPayment */
            $newPayment = Mage::getModel('pensopay/payment');

            $newPayment->setData($postData);
            $newPayment->importFromRemotePayment($payment);
            $newPayment->setLink($paymentLink);
            $newPayment->setIsVirtualterminal(true);
            $newPayment->setData('id');
            $newPayment->save();

            if ($sendEmail) {
                /** @var PensoPay_Payment_Helper_Data $helper */
                $helper = Mage::helper('pensopay');
                $helper->sendEmail($postData['customer_email'], $postData['customer_name'] ?: '', $newPayment->getAmount(), $newPayment->getCurrencyCode(), $paymentLink);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        return true;
    }

    public function saveAndPayAction(): void
    {
        if ($this->getRequest()->isPost()) {
            if ($this->_createPaymentLink(false)) {
                $this->_getSession()->setPaymentLinkAutovisit(true);
            }
        }
        $this->_redirectToTerminal();
    }

    public function saveAndSendAction(): void
    {
        if ($this->getRequest()->isPost()) {
            $this->_createPaymentLink(true);
        }
        $this->_redirectToTerminal();
    }

    public function updateAndPayAction(): void
    {
        if ($this->getRequest()->isPost()) {
            if ($this->_updatePaymentLink(false)) {
                $this->_getSession()->setPaymentLinkAutovisit(true);
            }
        }
        $this->_redirectToTerminal();
    }

    public function updateAndSendAction(): void
    {
        if ($this->getRequest()->isPost()) {
            $this->_updatePaymentLink(true);
        }
        $this->_redirectToTerminal();
    }

    public function updatePaymentStatusAction(): void
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();

        $incId = $request->getParam('id');

        /** @var PensoPay_Payment_Model_Payment $paymentModel */
        $paymentModel = Mage::getModel('pensopay/payment');
        if (empty($incId)) {
            $this->_redirectToTerminal($this->__('Payment not found.'));
            return;
        }
        try {
            $paymentModel->load($incId);
            $paymentModel->updatePaymentRemote();
            $this->_getSession()->addSuccess($this->__('Payment updated successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        $this->_redirect('*/*/edit', ['id' => $paymentModel->getId()]);
    }

    protected function _getPayment(): ?PensoPay_Payment_Model_Payment
    {
        if (!$this->_payment) {
            /** @var Mage_Core_Controller_Request_Http $request */
            $request = $this->getRequest();

            $incId = $request->getParam('id');

            /** @var PensoPay_Payment_Model_Payment $paymentModel */
            $paymentModel = Mage::getModel('pensopay/payment');
            if (!empty($incId)) { //Existing payment
                $paymentModel->load($incId);

                if (!$paymentModel->getId()) {
                    $this->_redirectToTerminal($this->__('Payment not found.'));
                }
                $this->_payment = $paymentModel;
            } else {
                $this->_getSession()->addError($this->__('No payment id specified.'));
            }
        }
        return $this->_payment;
    }

    protected function _genericPaymentCallback(string $action): bool
    {
        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        $paymentModel = $this->_getPayment();
        if ($paymentModel) {
            try {
                if (in_array($action, ['capture', 'refund'])) {
                    $payment = $api->{$action}($paymentModel->getReferenceId(), $paymentModel->getAmount());
                } else {
                    $payment = $api->{$action}($paymentModel->getReferenceId());
                }
                $this->_getSession()->addSuccess($this->__('Successfully processed Order ID: ') . $paymentModel->getOrderId());

                $paymentModel->importFromRemotePayment($payment);
                $paymentModel->save();
            } catch (Exception $e) {
                if ($this->_redirect) {
                    $this->_redirectToTerminal($e->getMessage());
                    return true;
                }
                $this->_getSession()->addError($e->getMessage());
                return false;
            }
        }

        if ($this->_redirect) {
            $this->_redirectToTerminal();
        }
        return true;
    }

    public function cancelPaymentAction(): void
    {
        $this->_genericPaymentCallback('cancel');
    }

    public function capturePaymentAction(): void
    {
        $this->_genericPaymentCallback('capture');
    }

    public function refundPaymentAction(): void
    {
        $this->_genericPaymentCallback('refund');
    }

    protected function _genericMassPaymentAction(string $action): void
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();

        $ids = $request->getParam('id');
        if (!empty($ids)) {
            /** @var PensoPay_Payment_Model_Resource_Payment_Collection $paymentCollection */
            $paymentCollection = Mage::getResourceModel('pensopay/payment_collection');
            $paymentCollection->addFieldToFilter('id', ['in' => $ids]);

            $this->_redirect = false;

            if (!empty($paymentCollection->getItems())) {
                /** @var PensoPay_Payment_Model_Payment $payment */
                foreach ($paymentCollection as $payment) {
                    if ($payment->{'can' . ucfirst($action)}()) { //canCapture, canCancel, canRefund
                        $this->_payment = $payment;
                        $this->_genericPaymentCallback($action);
                    }
                }
            } else {
                $this->_getSession()->addError($this->__('No payments found.'));
            }
        }
        $this->_redirectToTerminal();
    }

    public function massCaptureAction(): void
    {
        $this->_genericMassPaymentAction('capture');
    }

    public function massCancelAction(): void
    {
        $this->_genericMassPaymentAction('cancel');
    }

    public function massRefundAction(): void
    {
        $this->_genericMassPaymentAction('refund');
    }

    /**
     * Mass capture action
     *
     * @throws Mage_Core_Exception
     */
    public function orderMassCaptureAction(): void
    {
        $orderIds = $this->getRequest()->getPost('order_ids', []);

        foreach ($orderIds as $orderId) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $orderPayment = $order->getPayment();

            if (!$orderPayment || !$orderPayment->getMethodInstance() instanceof PensoPay_Payment_Model_Method) {
                $this->_getSession()->addError($this->__('%s Order was not placed using PensoPay', $order->getIncrementId()));
                continue;
            }

            try {
                if (!$order->canInvoice()) {
                    $this->_getSession()->addError($this->__('Could not create invoice for %s', $order->getIncrementId()));
                    continue;
                }

                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

                if (!$invoice->getTotalQty()) {
                    $this->_getSession()->addError($this->__('Cannot create an invoice without products for %s.', $order->getIncrementId()));
                    continue;
                }

                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transactionSave->save();
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Invoice and capture failed for %s: %s', $order->getIncrementId(), $e->getMessage()));
                continue;
            }
        }

        $this->_redirect('*/sales_order/');
    }
}
