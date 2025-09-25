<?php

namespace Vendor\CharWash\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OdinDev\CharWash\CharWash;
use OdinDev\CharWash\Config\CharWashConfig;

/**
 * Observer to sanitize product data before save using CharWash
 */
class SanitizeProductBeforeSave implements ObserverInterface
{
    public function __construct()
    {
        // Configure CharWash for Magento
        $cacheDir = BP . '/var/cache/charwash';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        CharWashConfig::setHtmlPurifierCachePath($cacheDir);
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        // Sanitize text attributes
        if ($product->getName()) {
            $product->setName(CharWash::sanitize($product->getName()));
        }

        if ($product->getSku()) {
            $product->setSku(CharWash::sanitizeUnicode($product->getSku()));
        }

        // Sanitize HTML attributes
        if ($product->getDescription()) {
            $product->setDescription(CharWash::sanitizeHtml($product->getDescription()));
        }

        if ($product->getShortDescription()) {
            $product->setShortDescription(CharWash::sanitizeHtml($product->getShortDescription()));
        }

        // Handle custom attributes
        if ($product->getData('meta_description')) {
            $product->setData(
                'meta_description',
                CharWash::sanitize($product->getData('meta_description'))
            );
        }
    }
}

// ---------------------------------------------

namespace Vendor\CharWash\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use OdinDev\CharWash\CharWash;
use OdinDev\CharWash\Config\CharWashConfig;

/**
 * CharWash Helper for Magento 2
 */
class Data extends AbstractHelper
{
    public function __construct(Context $context)
    {
        parent::__construct($context);

        // Configure CharWash
        $cacheDir = BP . '/var/cache/charwash';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        CharWashConfig::setHtmlPurifierCachePath($cacheDir);
    }

    /**
     * Complete sanitization
     */
    public function sanitize(string $text): string
    {
        return CharWash::sanitize($text);
    }

    /**
     * HTML sanitization
     */
    public function sanitizeHtml(string $html): string
    {
        return CharWash::sanitizeHtml($html);
    }

    /**
     * Office/Word cleanup
     */
    public function sanitizeOffice(string $text): string
    {
        return CharWash::sanitizeOffice($text);
    }

    /**
     * Unicode normalization
     */
    public function sanitizeUnicode(string $text): string
    {
        return CharWash::sanitizeUnicode($text);
    }
}

// ---------------------------------------------

namespace Vendor\CharWash\Model;

use Magento\Framework\Model\AbstractModel;
use OdinDev\CharWash\CharWash;

/**
 * Custom model with automatic CharWash sanitization
 */
class CustomContent extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Vendor\CharWash\Model\ResourceModel\CustomContent::class);
    }

    /**
     * Sanitize data before save
     */
    public function beforeSave()
    {
        if ($this->getData('title')) {
            $this->setData('title', CharWash::sanitize($this->getData('title')));
        }

        if ($this->getData('content')) {
            $this->setData('content', CharWash::sanitizeHtml($this->getData('content')));
        }

        // Clean data pasted from Word/Excel
        if ($this->getData('imported_content')) {
            $this->setData('imported_content', CharWash::sanitizeOffice($this->getData('imported_content')));
        }

        return parent::beforeSave();
    }
}

// ---------------------------------------------

namespace Vendor\CharWash\Plugin;

use Magento\Cms\Model\Page;
use OdinDev\CharWash\CharWash;

/**
 * Plugin to sanitize CMS page content
 */
class CmsPagePlugin
{
    /**
     * Sanitize CMS page content before save
     */
    public function beforeSave(Page $page)
    {
        if ($page->getTitle()) {
            $page->setTitle(CharWash::sanitize($page->getTitle()));
        }

        if ($page->getContent()) {
            $page->setContent(CharWash::sanitizeHtml($page->getContent()));
        }

        if ($page->getMetaDescription()) {
            $page->setMetaDescription(CharWash::sanitize($page->getMetaDescription()));
        }

        return [];
    }
}

// ---------------------------------------------
// File: etc/events.xml

/*
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
    <event name="catalog_product_save_before">
        <observer name="vendor_charwash_sanitize_product"
                  instance="Vendor\CharWash\Observer\SanitizeProductBeforeSave"/>
    </event>
</config>
*/

// ---------------------------------------------
// File: etc/di.xml

/*
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Magento\Cms\Model\Page">
        <plugin name="vendor_charwash_cms_page_plugin"
                type="Vendor\CharWash\Plugin\CmsPagePlugin"/>
    </type>
</config>
*/

// ---------------------------------------------
// File: etc/module.xml

/*
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Vendor_CharWash" setup_version="1.0.0">
        <sequence>
            <module name="Magento_Catalog"/>
            <module name="Magento_Cms"/>
        </sequence>
    </module>
</config>
*/

// ---------------------------------------------
// File: registration.php

/*
<?php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Vendor_CharWash',
    __DIR__
);
*/