<?php

class PensoPay_Payment_Model_Payment extends Mage_Core_Model_Abstract
{
    protected PensoPay_Payment_Helper_Data $_helper;

    public const STATE_INITIAL = 'initial';
    public const STATE_NEW     = 'new';
    public const STATE_PROCESSED = 'processed';
    public const STATE_PENDING = 'pending';
    public const STATE_REJECTED = 'rejected';

    public const STATUS_APPROVED = 20000;
    public const STATUS_WAITING_APPROVAL = 20200;
    public const STATUS_3D_SECURE_REQUIRED = 30100;
    public const STATUS_REJECTED_BY_ACQUIRER = 40000;
    public const STATUS_REQUEST_DATA_ERROR = 40001;
    public const STATUS_AUTHORIZATION_EXPIRED = 40002;
    public const STATUS_ABORTED = 40003;
    public const STATUS_GATEWAY_ERROR = 50000;
    public const COMMUNICATIONS_ERROR_ACQUIRER = 50300;

    public const OPERATION_CAPTURE = 'capture';
    public const OPERATION_AUTHORIZE = 'authorize';
    public const OPERATION_CANCEL = 'cancel';
    public const OPERATION_REFUND = 'refund';
    public const OPERATION_MOBILEPAY_SESSION = 'session';

    public const FRAUD_PROBABILITY_HIGH = 'high';
    public const FRAUD_PROBABILITY_NONE = 'none';

    /** @var array<string, mixed> */
    protected array $_lastOperation = [];

    public const STATUS_CODES =
        [
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_WAITING_APPROVAL => 'Waiting approval',
            self::STATUS_3D_SECURE_REQUIRED => '3D Secure is required',
            self::STATUS_REJECTED_BY_ACQUIRER => 'Rejected By Acquirer',
            self::STATUS_REQUEST_DATA_ERROR => 'Request Data Error',
            self::STATUS_AUTHORIZATION_EXPIRED => 'Authorization expired',
            self::STATUS_ABORTED => 'Aborted',
            self::STATUS_GATEWAY_ERROR => 'Gateway Error',
            self::COMMUNICATIONS_ERROR_ACQUIRER => 'Communications Error (with Acquirer)',
        ];

    /**
     * States in which the payment can't be updated anymore
     * Used for cron.
     */
    public const FINALIZED_STATES =
        [
            self::STATE_REJECTED,
            self::STATE_PROCESSED,
        ];

    public function __construct()
    {
        parent::__construct();
        $this->_init('pensopay/payment');
        $this->_helper = Mage::helper('pensopay');
    }

    public function getDisplayStatus(): string
    {
        $lastCode = (int) $this->getLastCode();

        $status = '';
        if ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_CAPTURE) {
            $status = $this->_helper->__('Captured');
        } elseif ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_CANCEL) {
            $status = $this->_helper->__('Cancelled');
        } elseif ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_REFUND) {
            $status = $this->_helper->__('Refunded');
        } elseif (!empty(self::STATUS_CODES[$lastCode])) {
            $status = self::STATUS_CODES[$lastCode];
        }
        return sprintf('%s (%s)', $status, $this->getState());
    }

    public function cancel(): self
    {
        $api = Mage::getModel('pensopay/api');
        $this->importFromRemotePayment($api->cancel($this->getReferenceId()));
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        if (!empty($this->getData('metadata'))) {
            return Mage::helper('core')->jsonDecode($this->getData('metadata'));
        }
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFirstOperation(): array
    {
        if (!empty($this->getOperations())) {
            $operations = Mage::helper('core')->jsonDecode($this->getOperations());
            if (!empty($operations) && is_array($operations)) {
                $firstOp = array_shift($operations);
                if (!empty($firstOp) && is_array($firstOp)) {
                    return [
                        'type' => $firstOp['type'],
                        'code' => $firstOp['qp_status_code'],
                        'msg'  => $firstOp['qp_status_msg'],
                    ];
                }
            }
        }
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastOperation(): array
    {
        if (empty($this->_lastOperation)) {
            if (!empty($this->getOperations())) {
                $operations = Mage::helper('core')->jsonDecode($this->getOperations());
                if (!empty($operations) && is_array($operations)) {
                    $lastOp = array_pop($operations);
                    if (!empty($lastOp) && is_array($lastOp)) {
                        $this->_lastOperation = [
                            'type' => $lastOp['type'],
                            'code' => $lastOp['qp_status_code'],
                            'msg'  => $lastOp['qp_status_msg'],
                        ];
                    }
                }
            }
        }
        return $this->_lastOperation;
    }

    public function getLastMessage(): mixed
    {
        return $this->getLastOperation()['msg'] ?? null;
    }

    public function getLastType(): mixed
    {
        return $this->getLastOperation()['type'] ?? null;
    }

    public function getLastCode(): mixed
    {
        return $this->getLastOperation()['code'] ?? null;
    }

    public function importFromRemotePayment(stdClass $payment): void
    {
        if (!Mage::getStoreConfigFlag(PensoPay_Payment_Model_Config::XML_PATH_TESTMODE_ENABLED, $this->getStore()) && $payment->test_mode) {
            $this->setState(self::STATE_REJECTED);
            return;
        }

        $coreHelper = Mage::helper('core');
        $paymentAsArray = $coreHelper->jsonDecode($coreHelper->jsonEncode($payment));
        $this->setReferenceId($paymentAsArray['id']);
        unset($paymentAsArray['id']); //We don't want to override the object id with the remote id
        $this->addData($paymentAsArray);
        if (isset($paymentAsArray['link']) && !empty($paymentAsArray['link'])) {
            if (is_array($paymentAsArray['link'])) {
                $this->setLink($paymentAsArray['link']['url']);
            } else {
                $this->setLink($paymentAsArray['link']);
            }
        }
        $this->setAmount($paymentAsArray['basket'][0]['item_price']);
        $this->setCurrencyCode($paymentAsArray['currency']);
        if (!empty($paymentAsArray['metadata']) && is_array($paymentAsArray['metadata'])) {
            $this->setFraudProbability($paymentAsArray['metadata']['fraud_suspected'] || $paymentAsArray['metadata']['fraud_reported'] ? self::FRAUD_PROBABILITY_HIGH : self::FRAUD_PROBABILITY_NONE);
        }
        $this->setOperations($coreHelper->jsonEncode($paymentAsArray['operations']));
        $this->setMetadata($coreHelper->jsonEncode($paymentAsArray['metadata']));
        $this->setHash(md5($this->getReferenceId() . $this->getLink() . $this->getAmount()));

        if (!empty($payment->operations)) {
            $amountCaptured = 0;
            $amountRefunded = 0;
            foreach ($payment->operations as $operation) {
                if ($operation->type == 'capture') {
                    $amountCaptured += $operation->amount;
                } elseif ($operation->type == 'refund') {
                    $amountRefunded += $operation->amount;
                }
            }
            $this->setAmountCaptured($amountCaptured / 100);
            $this->setAmountRefunded($amountRefunded / 100);
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        if ($order->getId() && in_array($this->getLastType(), [
            self::OPERATION_AUTHORIZE,
            self::OPERATION_CAPTURE,
        ], true) && $this->getLastCode() == self::STATUS_APPROVED) {
            $status = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_ORDER_STATUS_AFTERPAYMENT);
            if ($order->getStatus() != $status) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status);
                $order->save();
            }
        }
    }

    /**
     * Updates payment data from remote gateway.
     *
     * @throws Exception
     */
    public function updatePaymentRemote(): void
    {
        if (!$this->getId()) {
            throw new Exception($this->_helper->__('Payment not loaded.'));
        }

        if (!$this->getReferenceId()) {
            throw new Exception($this->_helper->__('Reference id not found.'));
        }

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        $paymentInfo = $api->getPayment($this->getReferenceId(), $this->getStore());
        $this->importFromRemotePayment($paymentInfo);
        $this->save();
    }

    public function canCapture(): bool
    {
        return $this->getState() === self::STATE_NEW;
    }

    public function canCancel(): bool
    {
        return $this->getState() === self::STATE_NEW;
    }

    public function canRefund(): bool
    {
        return ($this->getState() === self::STATE_PROCESSED && ($this->getAmount() !== $this->getAmountRefunded()));
    }
}
