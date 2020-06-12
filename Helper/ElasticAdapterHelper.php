<?php

namespace Algolia\AlgoliaSearch\Helper;

use Algolia\AlgoliaSearch\Helper\Adapter\FiltersHelper;
use Algolia\AlgoliaSearch\Helper\AdapterHelper;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Data as AlgoliaDataHelper;
use Magento\CatalogSearch\Helper\Data as CatalogSearchDataHelper;

class ElasticAdapterHelper
{
    /**
     * @var AdapterHelper
     */
    private $adapterHelper;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param AdapterHelper $adapterHelper
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        AdapterHelper $adapterHelper,
        ConfigHelper $configHelper
    ) {
        $this->adapterHelper = $adapterHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @return bool
     */
    public function replaceElasticSearchResults()
    {
        if (!$this->adapterHelper->isAllowed()
            || !(
                $this->adapterHelper->isSearch() ||
                $this->adapterHelper->isReplaceCategory() ||
                $this->adapterHelper->isReplaceAdvancedSearch() ||
                $this->adapterHelper->isLandingPage()
            )
        ) {
            return false;
        }

        return true;
    }

    public function getStoreId()
    {
        return $this->configHelper->getStoreId();
    }

    public function getFacets()
    {
        return $this->configHelper->getFacets($this->getStoreId());
    }

}
