<?php

/**
 * Utility helper for ViaBill configuration, state/status colors, payment link emails and country code conversion.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

class PensoPay_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    public const LOG_FILENAME = 'pensopay.log';

    public const XML_PATH_VIABILL_ENABLED = 'payment/pensopay_viabill/active';
    public const XML_PATH_VIABILL_SHOPID = 'payment/pensopay_viabill/shop_id';

    public const REGISTRY_STORE_KEY = 'penso_store_id';

    public function isViabillEnabled(): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_VIABILL_ENABLED);
    }

    public function getViabillId(): mixed
    {
        return Mage::getStoreConfig(self::XML_PATH_VIABILL_SHOPID);
    }

    public function getStateColorCode(string $value): string
    {
        return match ($value) {
            PensoPay_Payment_Model_Payment::STATE_INITIAL => 'yellow',
            PensoPay_Payment_Model_Payment::STATE_NEW, PensoPay_Payment_Model_Payment::STATE_PENDING => 'orange',
            PensoPay_Payment_Model_Payment::STATE_REJECTED => 'red',
            default => 'green',
        };
    }

    public function getStatusColorCode(int $value): string
    {
        return match ($value) {
            PensoPay_Payment_Model_Payment::STATUS_WAITING_APPROVAL => 'yellow',
            PensoPay_Payment_Model_Payment::STATUS_3D_SECURE_REQUIRED => 'orange',
            PensoPay_Payment_Model_Payment::STATUS_ABORTED, PensoPay_Payment_Model_Payment::STATUS_GATEWAY_ERROR, PensoPay_Payment_Model_Payment::COMMUNICATIONS_ERROR_ACQUIRER, PensoPay_Payment_Model_Payment::STATUS_AUTHORIZATION_EXPIRED, PensoPay_Payment_Model_Payment::STATUS_REJECTED_BY_ACQUIRER, PensoPay_Payment_Model_Payment::STATUS_REQUEST_DATA_ERROR => 'red',
            default => 'green',
        };
    }

    /**
     * @throws Exception
     */
    public function sendEmail(#[\SensitiveParameter]
        string $email, string $name, float|string $amount, string $currency, string $link): void
    {
        $emailTemplate  = Mage::getModel('core/email_template')->loadDefault('pensopay_virtualterminal_link');

        $vars = [
            'currency' => $currency,
            'amount'   => $amount,
            'link'     => $link,
        ];

        $salesContact = Mage::getStoreConfig('trans_email/ident_sales');

        if (empty($salesContact)) {
            throw new Exception($this->__('Could not send email. The sales contact is empty.'));
        }

        $emailTemplate->setSenderEmail($salesContact['email']);
        $emailTemplate->setSenderName($salesContact['name']);
        $emailTemplate->setTemplateSubject($this->__('Payment link'));

        if (!$emailTemplate->send($email, $name, $vars)) {
            throw new Exception('Could not send email.');
        }
    }

    /**
     * Sets the registry for the store id - used to make sure we get the right credentials for api use
     * @throws Mage_Core_Exception
     */
    public function setTransactionStoreId(int $storeId): self
    {
        Mage::unregister(self::REGISTRY_STORE_KEY);
        Mage::register(self::REGISTRY_STORE_KEY, $storeId);
        return $this;
    }

    /**
     * Returns 0 if not set
     */
    public function getTransactionStoreId(): int
    {
        return Mage::registry(self::REGISTRY_STORE_KEY) ?: 0;
    }

    public function convertCountryAlphas3To2(string $code = 'DK'): string
    {
        $countries = Mage::helper('core')->jsonDecode('{"AFG":"AF","ALA":"AX","ALB":"AL","DZA":"DZ","ASM":"AS","AND":"AD","AGO":"AO","AIA":"AI","ATA":"AQ","ATG":"AG","ARG":"AR","ARM":"AM","ABW":"AW","AUS":"AU","AUT":"AT","AZE":"AZ","BHS":"BS","BHR":"BH","BGD":"BD","BRB":"BB","BLR":"BY","BEL":"BE","BLZ":"BZ","BEN":"BJ","BMU":"BM","BTN":"BT","BOL":"BO","BIH":"BA","BWA":"BW","BVT":"BV","BRA":"BR","VGB":"VG","IOT":"IO","BRN":"BN","BGR":"BG","BFA":"BF","BDI":"BI","KHM":"KH","CMR":"CM","CAN":"CA","CPV":"CV","CYM":"KY","CAF":"CF","TCD":"TD","CHL":"CL","CHN":"CN","HKG":"HK","MAC":"MO","CXR":"CX","CCK":"CC","COL":"CO","COM":"KM","COG":"CG","COD":"CD","COK":"CK","CRI":"CR","CIV":"CI","HRV":"HR","CUB":"CU","CYP":"CY","CZE":"CZ","DNK":"DK","DKK":"DK","DJI":"DJ","DMA":"DM","DOM":"DO","ECU":"EC","Sal":"El","GNQ":"GQ","ERI":"ER","EST":"EE","ETH":"ET","FLK":"FK","FRO":"FO","FJI":"FJ","FIN":"FI","FRA":"FR","GUF":"GF","PYF":"PF","ATF":"TF","GAB":"GA","GMB":"GM","GEO":"GE","DEU":"DE","GHA":"GH","GIB":"GI","GRC":"GR","GRL":"GL","GRD":"GD","GLP":"GP","GUM":"GU","GTM":"GT","GGY":"GG","GIN":"GN","GNB":"GW","GUY":"GY","HTI":"HT","HMD":"HM","VAT":"VA","HND":"HN","HUN":"HU","ISL":"IS","IND":"IN","IDN":"ID","IRN":"IR","IRQ":"IQ","IRL":"IE","IMN":"IM","ISR":"IL","ITA":"IT","JAM":"JM","JPN":"JP","JEY":"JE","JOR":"JO","KAZ":"KZ","KEN":"KE","KIR":"KI","PRK":"KP","KOR":"KR","KWT":"KW","KGZ":"KG","LAO":"LA","LVA":"LV","LBN":"LB","LSO":"LS","LBR":"LR","LBY":"LY","LIE":"LI","LTU":"LT","LUX":"LU","MKD":"MK","MDG":"MG","MWI":"MW","MYS":"MY","MDV":"MV","MLI":"ML","MLT":"MT","MHL":"MH","MTQ":"MQ","MRT":"MR","MUS":"MU","MYT":"YT","MEX":"MX","FSM":"FM","MDA":"MD","MCO":"MC","MNG":"MN","MNE":"ME","MSR":"MS","MAR":"MA","MOZ":"MZ","MMR":"MM","NAM":"NA","NRU":"NR","NPL":"NP","NLD":"NL","ANT":"AN","NCL":"NC","NZL":"NZ","NIC":"NI","NER":"NE","NGA":"NG","NIU":"NU","NFK":"NF","MNP":"MP","NOR":"NO","OMN":"OM","PAK":"PK","PLW":"PW","PSE":"PS","PAN":"PA","PNG":"PG","PRY":"PY","PER":"PE","PHL":"PH","PCN":"PN","POL":"PL","PRT":"PT","PRI":"PR","QAT":"QA","REU":"RE","ROU":"RO","RUS":"RU","RWA":"RW","BLM":"BL","SHN":"SH","KNA":"KN","LCA":"LC","MAF":"MF","SPM":"PM","VCT":"VC","WSM":"WS","SMR":"SM","STP":"ST","SAU":"SA","SEN":"SN","SRB":"RS","SYC":"SC","SLE":"SL","SGP":"SG","SVK":"SK","SVN":"SI","SLB":"SB","SOM":"SO","ZAF":"ZA","SGS":"GS","SSD":"SS","ESP":"ES","LKA":"LK","SDN":"SD","SUR":"SR","SJM":"SJ","SWZ":"SZ","SWE":"SE","CHE":"CH","SYR":"SY","TWN":"TW","TJK":"TJ","TZA":"TZ","THA":"TH","TLS":"TL","TGO":"TG","TKL":"TK","TON":"TO","TTO":"TT","TUN":"TN","TUR":"TR","TKM":"TM","TCA":"TC","TUV":"TV","UGA":"UG","UKR":"UA","ARE":"AE","GBR":"GB","USA":"US","UMI":"UM","URY":"UY","UZB":"UZ","VUT":"VU","VEN":"VE","VNM":"VN","VIR":"VI","WLF":"WF","ESH":"EH","YEM":"YE","ZMB":"ZM","ZWE":"ZW","GBP":"GB","RUB":"RU","NOK":"NO"}', true);

        if (!isset($countries[$code])) {
            return Mage::getStoreConfig('general/country/default', Mage::app()->getStore()?->getStoreId());
        }
        return $countries[$code];
    }
}
