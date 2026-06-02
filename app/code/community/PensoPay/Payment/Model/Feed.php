<?php

declare(strict_types=1);

class PensoPay_Payment_Model_Feed extends Mage_AdminNotification_Model_Feed
{
    #[\Override]
    public function getFeedUrl(): string
    {
        return 'https://pensopay.com/magento-feed.xml';
    }

    /**
     * Get last update timestamp
     *
     * @return string|false
     */
    #[\Override]
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('pensopay_feed_notifications_lastcheck');
    }

    /**
     * Set last update timestamp
     */
    #[\Override]
    public function setLastUpdate(): self
    {
        Mage::app()->saveCache(time(), 'pensopay_feed_notifications_lastcheck');

        return $this;
    }
}
