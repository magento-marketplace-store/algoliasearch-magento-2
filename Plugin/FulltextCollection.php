<?php

namespace Algolia\AlgoliaSearch\Plugin;

use Algolia\AlgoliaSearch\Helper\AdapterHelper;
use Algolia\AlgoliaSearch\Helper\ElasticAdapterHelper;
use Magento\Catalog\Model\ProductFactory;

class FulltextCollection
{
    /** @var AdapterHelper */
    private $adapterHelper;

    /** @var ElasticAdapterHelper */
    private $esAdapterHelper;

    /** @var ProductFactory */
    private $productFactory;

    /** @var \Magento\Catalog\Model\Product */
    private $product;

    private $facets;

    /**
     * @param AdapterHelper $adapterHelper
     */
    public function __construct(
        AdapterHelper $adapterHelper,
        ElasticAdapterHelper $esAdapterHelper,
        ProductFactory $productFactory
    ) {
        $this->adapterHelper = $adapterHelper;
        $this->esAdapterHelper = $esAdapterHelper;
        $this->productFactory = $productFactory;
    }

    /**
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $subject
     * @param $field
     * @param null $condition
     */
    public function beforeAddFieldToFilter(
        \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $subject,
        $field,
        $condition = null
    ) {
        if (!$condition || !$this->esAdapterHelper->replaceElasticSearchResults()) {
            return [$field, $condition];
        }

        if (is_array($this->getFacets()) && in_array($field, $this->getFacets())) {
            $condition = $this->getOptionIdByLabel($field, $condition);
        }

        return [$field, $condition];
    }

    /**
     * @param string $attributeCode
     * @param null $optionLabel
     * @return string
     */
    private function getOptionIdByLabel($attributeCode, $optionLabel = null)
    {
        if ($optionLabel && !is_array($optionLabel)) {
            $product = $this->getProduct();
            $isAttributeExist = $product->getResource()->getAttribute($attributeCode);
            if ($isAttributeExist && $isAttributeExist->usesSource()) {
                $optionLabel = $isAttributeExist->getSource()->getOptionId($optionLabel);
            }
        }

        return $optionLabel;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if (!$this->product) {
            $this->product = $this->productFactory->create();
        }

        return $this->product;
    }

    /**
     * @return array
     */
    public function getFacets()
    {
        if (!$this->facets) {
            $facets = [];
            $configFacets = $this->esAdapterHelper->getFacets();
            if (is_array($configFacets) && count($configFacets)) {
                $facets = array_map(function ($facet) {
                    return $facet['attribute'];
                }, $configFacets);
            }
            $this->facets = $facets;
        }

        return $this->facets;
    }

}
