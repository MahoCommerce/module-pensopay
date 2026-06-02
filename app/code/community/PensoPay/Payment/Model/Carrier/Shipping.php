<?php

class PensoPay_Payment_Model_Carrier_Shipping extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * @var string
     */
    protected $_code = 'pensopay_mobilepay';

    #[\Override]
    public function collectRates(Mage_Shipping_Model_Rate_Request $request): Mage_Shipping_Model_Rate_Result|false
    {
        if (!Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/active', $this->getStore()) || !Mage::app()->getRequest()->getParam('mobilepay')) {
            return false;
        }
        $result = Mage::getModel('shipping/rate_result');
        $result->append($this->_getDefaultRate());

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getAllowedMethods(): array
    {
        return [
            $this->_code => $this->getConfigData('name'),
        ];
    }

    protected function _getDefaultRate(): Mage_Shipping_Model_Rate_Result_Method
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle((string) $this->getConfigData('title'));
        $rate->setMethod($this->_code);
        $rate->setMethodTitle((string) $this->getConfigData('name'));
        $rate->setPrice((string) $this->getConfigData('price'));
        $rate->setCost(0);

        return $rate;
    }

    #[\Override]
    public function isTrackingAvailable(): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    private function getAvailableMethods(): array
    {
        return [
            'store_pick_up' => $this->getShipping1Title(),
            'home_delivery' => $this->getShipping2Title(),
            'registered_box' => $this->getShipping3Title(),
            'unregistered_box' => $this->getShipping4Title(),
            'pick_up_point' => $this->getShipping5Title(),
            'own_delivery' => $this->getShipping6Title(),
        ];
    }

    /**
     * @return array<string, array{title: string, price: string}>
     */
    public function getMobilePayMethods(): array
    {
        $methods = $this->getAvailableMethods();
        $data = [];
        foreach ($methods as $code => $title) {
            $price = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_' . $code, $this->getStore());
            if (!$price) {
                $price = 0;
            }
            $data[$code] = [
                'title' => $title,
                'price' => Mage::helper('core')->currency($price, true, false),
            ];
        }
        return $data;
    }

    /**
     * @return array{title: string, price: string}|false
     */
    public function getMethodByCode(string $code): array|false
    {
        $methods = $this->getAvailableMethods();
        if (isset($methods[$code])) {
            $price = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_' . $code, $this->getStore());
            if (!$price) {
                $price = 0;
            }
            return [
                'title' => $methods[$code],
                'price' => number_format($price, 2),
            ];
        }
        return false;
    }

    public function getShipping1Title(): string
    {
        $title = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_store_pick_up_title', $this->getStore());
        return $title ?: Mage::helper('pensopay')->__('Hent i butikken');
    }

    public function getShipping2Title(): string
    {
        $title = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_home_delivery_title', $this->getStore());
        return $title ?: Mage::helper('pensopay')->__('Ordren leveres til din hjemmeadresse');
    }

    public function getShipping3Title(): string
    {
        $title = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_registered_box_title', $this->getStore());
        return $title ?: Mage::helper('pensopay')->__('Afhentning i en pakkeshop (registered_box)');
    }

    public function getShipping4Title(): string
    {
        $title = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_unregistered_box_title', $this->getStore());
        return $title ?: Mage::helper('pensopay')->__('Afhentning i en pakkeshop (unregistered_box)');
    }

    public function getShipping5Title(): string
    {
        $title = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_pick_up_point_title', $this->getStore());
        return $title ?: Mage::helper('pensopay')->__('Afhentning i en pakkeshop (pick_up_point)');
    }

    public function getShipping6Title(): string
    {
        $title = Mage::getStoreConfig('payment/pensopay_mobilepay_checkout/shipping_own_delivery_title', $this->getStore());
        return $title ?: Mage::helper('pensopay')->__('Ordren leveres til din hjemmeadresse');
    }
}
