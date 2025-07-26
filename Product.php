<?php
/**
 * Copyright © magebig.com - All rights reserved.
 * See LICENSE.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace MageBig\WidgetPlus\Block;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Catalog Products List widget block
 * Class ProductsList.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Widget\Block\BlockInterface
{
    const CACHE_TAGS = 'WIDGETPLUS_PRODUCT';

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \MageBig\WidgetPlus\Model\ResourceModel\Widget\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var
     */
    protected $_productCollection;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $categoryModel;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Product constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \MageBig\WidgetPlus\Model\ResourceModel\Widget\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\Category $categoryModel
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \MageBig\WidgetPlus\Model\ResourceModel\Widget\CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\Category $categoryModel,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->httpContext = $httpContext;
        $this->_collectionFactory = $collectionFactory;
        $this->categoryModel = $categoryModel;
        parent::__construct($context, $data);
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    protected function _construct()
    {
                \Magento\Framework\Profiler::start('widgetplus__construct');

        parent::_construct();
        $this->addColumnCountLayoutDepend('empty', 6)->addColumnCountLayoutDepend(
            '1column',
            5
        )->addColumnCountLayoutDepend('2columns-left', 4)->addColumnCountLayoutDepend(
            '2columns-right',
            4
        )->addColumnCountLayoutDepend('3columns', 3);

                \Magento\Framework\Profiler::stop('widgetplus__construct');

    }

    /**
     * Get block cache life time
     *
     * @return int|bool|null
     */
     protected function getCacheLifetime()
    {
        \Magento\Framework\Profiler::start('widgetplus_cache_lifetime');

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('----------------- get data ---------------');
        $logger->info(print_r($this->hasData('cache_lifetime'), true));

        if (!$this->hasData('cache_lifetime')) {
            $logger->info('Setting default cache lifetime to 86400');
            \Magento\Framework\Profiler::stop('widgetplus_cache_lifetime');
            return 86400; // 1 day
        }

        $cacheLifetime = $this->getData('cache_lifetime');

        $logger->info('-------cache_lifetime-------- ');
        $logger->info(print_r($cacheLifetime, true));

        \Magento\Framework\Profiler::stop('widgetplus_cache_lifetime');

        return (int)$cacheLifetime;
    }


    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCacheKeyInfo()
    {
        return [
            'MAGEBIG_WIDGETPLUS_PRODUCT',
            $this->getPriceCurrency()->getCurrency()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            $this->serializer->serialize($this->getRequest()->getParams()),
            $this->getWidgetId(),
            $this->getTemplateFile(),
            'base_url' => $this->getBaseUrl()
        ];
    }

    /**
     * @return PriceCurrencyInterface|mixed
     */
    private function getPriceCurrency()
    {
                \Magento\Framework\Profiler::start('widgetplus_price_currency');

        if ($this->priceCurrency === null) {
            $this->priceCurrency = \Magento\Framework\App\ObjectManager::getInstance()->get(PriceCurrencyInterface::class);
        }
                \Magento\Framework\Profiler::stop('widgetplus_price_currency');

        return $this->priceCurrency;
    }

    /**
     * @return int|string
     */
    public function getWidgetId()
    {        \Magento\Framework\Profiler::start('widgetplus_widget_id');

        $widgetId = crc32($this->serializer->serialize($this->getData()));
        $widgetId = 'widgetplus-' . $widgetId;
        \Magento\Framework\Profiler::stop('widgetplus_widget_id');

        return $widgetId;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|null|void
     */
    protected function _getProductCollection()
    {
                \Magento\Framework\Profiler::start('widgetplus_get_product_collection');

        if ($this->_productCollection === null) {
            $this->_productCollection = $this->initializeProductCollection();
        }
        \Magento\Framework\Profiler::stop('widgetplus_get_product_collection');

        return $this->_productCollection;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|null|void
     */
    protected function initializeProductCollection()
    {
                \Magento\Framework\Profiler::start('widgetplus_initialize_collection');

        $limit = (int)$this->getData('limit');
        $value = $this->getData('product_type');
        $params = [];

        if ($this->getData('period')) {
            $params['period'] = $this->getData('period');
        }
        if ($this->getData('category_ids')) {
            $params['category_ids'] = explode(',', $this->getData('category_ids'));
        }
        if ($this->getData('product_ids')) {
            $params['product_ids'] = explode(',', $this->getData('product_ids'));
        }
        if ($this->getCustomerId()) {
            $params['customer_id'] = $this->getCustomerId();
        }

        $collection = $this->_collectionFactory->create()->getProducts('product', $value, $params, $limit);
Check
                \Magento\Framework\Profiler::stop('widgetplus_initialize_collection');

        return $collection;
    }

    /**
     * Retrieve loaded category collection
     *
     * @return AbstractCollection
     */
    public function getLoadedProductCollection()
    {
                     \Magento\Framework\Profiler::start('widgetplus_loaded_product_collection');
        $collection = $this->_getProductCollection();
        \Magento\Framework\Profiler::stop('widgetplus_loaded_product_collection');
        return $collection;

    }

    /**
     * @param AbstractCollection $collection
     *
     * @return $this
     */
    public function setCollection($collection)
    {
                \Magento\Framework\Profiler::start('widgetplus_set_collection');

        $this->_productCollection = $collection;
                \Magento\Framework\Profiler::stop('widgetplus_set_collection');


        return $this;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
                \Magento\Framework\Profiler::start('widgetplus_get_identities');

        $identities = [];
        foreach ($this->_getProductCollection() as $item) {
            $identities = array_merge($identities, $item->getIdentities());
        }
                \Magento\Framework\Profiler::stop('widgetplus_get_identities');


        return $identities;
    }
}
