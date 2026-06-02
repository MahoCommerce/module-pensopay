<?php

class PensoPay_Payment_Model_Method extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'pensopay';

    /**
     * Form block type
     *
     * @see PensoPay_Payment_Block_Form for the corresponding class
     * @var string
     */
    protected $_formBlockType = 'pensopay/form';

    /**
     * Info block type
     *
     * @see PensoPay_Payment_Block_Info for the corresponding class
     * @var string
     */
    protected $_infoBlockType = 'pensopay/info';

    /** @var bool */
    protected $_isGateway                   = true;
    /** @var bool */
    protected $_canOrder                    = true;
    /** @var bool */
    protected $_canAuthorize                = true;
    /** @var bool */
    protected $_canCapture                  = true;
    /** @var bool */
    protected $_canCapturePartial           = true;
    /** @var bool */
    protected $_canCaptureOnce              = true;
    /** @var bool */
    protected $_canRefund                   = true;
    /** @var bool */
    protected $_canRefundInvoicePartial     = true;
    /** @var bool */
    protected $_canVoid                     = true;
    /** @var bool */
    protected $_canUseInternal              = true;
    /** @var bool */
    protected $_canUseCheckout              = true;
    /** @var bool */
    protected $_canUseForMultishipping      = true;
    /** @var bool */
    protected $_isInitializeNeeded          = true;
    /** @var bool */
    protected $_canFetchTransactionInfo     = true;
    /** @var bool */
    protected $_canReviewPayment            = true;
    /** @var bool */
    protected $_canCreateBillingAgreement   = true;
    /** @var bool */
    protected $_canManageRecurringProfiles  = true;

    protected PensoPay_Payment_Model_Api $_api;

    protected PensoPay_Payment_Helper_Data $_helper;

    public function __construct()
    {
        parent::__construct();
        $this->_api = Mage::getModel('pensopay/api');
        $this->_helper = Mage::helper('pensopay');
    }

    /**
     * Set order status to pending
     *
     * @param string $paymentAction
     * @param \Maho\DataObject $stateObject
     */
    #[\Override]
    public function initialize($paymentAction, $stateObject): static
    {
        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending');
        $stateObject->setIsNotified(false);

        return parent::initialize($paymentAction, $stateObject);
    }

    private function _getSession(): Mage_Adminhtml_Model_Session
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param float $amount
     * @throws Exception
     */
    #[\Override]
    public function capture(\Maho\DataObject $payment, $amount): static
    {
        if ($payment->getInfoInstance()) {
            $order = $payment->getInfoInstance()->getOrder();
        } else {
            $order = $payment->getOrder();
        }

        /** @var PensoPay_Payment_Model_Payment $payment */
        $payment = Mage::getModel('pensopay/payment')->load($order->getIncrementId(), 'order_id');
        if (!$payment->getId()) {
            throw new Exception($this->_helper->__('Payment not found.'));
        }

        $amountCaptured = $payment->getAmountCaptured();
        if ($payment->getAmount() < ($amountCaptured + $amount)) {
            throw new Exception($this->_helper->__('Trying to capture more than authorized.'));
        }

        try {
            /** @var PensoPay_Payment_Helper_Data $helper */
            $helper = Mage::helper('pensopay');
            $helper->setTransactionStoreId($order->getStoreId());
            $paymentInfo = $this->_api->capture($payment->getReferenceId(), $amount);
            $payment->importFromRemotePayment($paymentInfo);

            $lastCode = $payment->getLastCode();
            if ($lastCode == PensoPay_Payment_Model_Payment::STATUS_APPROVED) {
                $payment->setAmountCaptured($amountCaptured + $amount);
                $this->createTransaction($order, $payment->getReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                $this->_getSession()->addSuccess($this->_helper->__('Payment captured online.'));
            } else {
                throw new Exception($payment->getLastMessage());
            }
        } catch (Exception $e) {
            Mage::log(sprintf('Capture error for: %s -- %s', $payment->getId(), $e->getMessage()));
        } finally {
            $payment->save();
            $order->save();

            if (isset($e)) {
                throw $e;
            } //rethrow it
        }

        return $this;
    }

    /**
     * @param float $amount
     * @throws Exception
     */
    #[\Override]
    public function refund(\Maho\DataObject $payment, $amount): static
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        /** @var PensoPay_Payment_Model_Payment $payment */
        $payment = Mage::getModel('pensopay/payment')->load($order->getIncrementId(), 'order_id');
        if (!$payment->getId()) {
            throw new Exception($this->_helper->__('Payment not found.'));
        }

        $amountRefunded = $payment->getAmountRefunded();
        if ($payment->getAmount() < ($amountRefunded + $amount)) {
            throw new Exception($this->_helper->__('Trying to refund more than captured.'));
        }
        try {
            /** @var PensoPay_Payment_Helper_Data $helper */
            $helper = Mage::helper('pensopay');
            $helper->setTransactionStoreId($order->getStoreId());
            $paymentInfo = $this->_api->refund($payment->getReferenceId(), $amount);
            $payment->importFromRemotePayment($paymentInfo);

            $lastCode = $payment->getLastCode();
            if ($lastCode == PensoPay_Payment_Model_Payment::STATUS_APPROVED) {
                $payment->setAmountRefunded($amountRefunded + $amount);
                $this->createTransaction($order, $payment->getReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
                $this->_getSession()->addSuccess($this->_helper->__('Amount refunded online.'));
            } else {
                throw new Exception($payment->getLastMessage());
            }
        } catch (Exception $e) {
            Mage::log(sprintf('Capture error for: %s -- %s', $payment->getId(), $e->getMessage()));
        } finally {
            $payment->save();
            $order->save();

            if (isset($e)) {
                throw $e;
            } //rethrow it
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function createTransaction(Mage_Sales_Model_Order $order, string $transactionId, string $type): Mage_Sales_Model_Order_Payment_Transaction
    {
        $orderPayment = $order->getPayment();
        if (!$orderPayment) {
            throw new Exception($this->_helper->__('Order payment not found.'));
        }
        $orderPayment->setLastTransId($transactionId);
        $orderPayment->save();

        $transaction = Mage::getModel('sales/order_payment_transaction');
        $transaction->setOrderPaymentObject($orderPayment);

        if (! $transaction = $transaction->loadByTxnId($transactionId)) {
            $transaction = Mage::getModel('sales/order_payment_transaction');
            $transaction->setOrderPaymentObject($orderPayment);
        }

        if ($type == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
            $transaction->setIsClosed(0);
        } else {
            $transaction->setIsClosed(1);
        }

        $transaction->setTxnId($transactionId);
        $transaction->setTxnType($type);
        $transaction->save();

        return $transaction;
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods(): mixed
    {
        if ($this->getConfigData('payment_methods') === 'specified') {
            return $this->getConfigData('payment_methods_specified');
        }

        return $this->getConfigData('payment_method');
    }

    /**
     * Get Order place redirect url
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return Mage::getUrl('pensopay/payment/redirect', ['_secure' => true]);
    }
}
