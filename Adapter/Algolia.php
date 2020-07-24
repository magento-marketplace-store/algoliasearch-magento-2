<?php

namespace Algolia\AlgoliaSearch\Adapter;

use Algolia\AlgoliaSearch\Adapter\Aggregation\Builder as AlgoliaAggregationBuilder;
use Algolia\AlgoliaSearch\Helper\AdapterHelper;
use AlgoliaSearch\AlgoliaConnectionException;
use Magento\Elasticsearch\SearchAdapter\Aggregation\Builder as AggregationBuilder;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Elasticsearch\SearchAdapter\QueryContainerFactory;
use Magento\Elasticsearch\SearchAdapter\ResponseFactory;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Elasticsearch\Elasticsearch5\SearchAdapter\Adapter as NativeAdapter;
use Magento\Framework\Search\Response\QueryResponse;
use Magento\Elasticsearch\Elasticsearch5\SearchAdapter\Mapper;
use Psr\Log\LoggerInterface;

/**
 * Algolia Search Adapter
 */
class Algolia extends NativeAdapter implements AdapterInterface
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var AdapterHelper
     */
    private $adapterHelper;

    /**
     * @var AlgoliaAggregationBuilder
     */
    private $algoliaAggregationBuilder;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var QueryContainerFactory
     */
    private $queryContainerFactory;

    /**
     * @param ResponseFactory $responseFactory
     * @param Mapper $mapper
     * @param AdapterHelper $adapterHelper
     * @param AlgoliaAggregationBuilder $algoliaAggregationBuilder
     */
    public function __construct(
        ConnectionManager $connectionManager,
        Mapper $mapper,
        ResponseFactory $responseFactory,
        AlgoliaAggregationBuilder $aggregationBuilder,
        QueryContainerFactory $queryContainerFactory,
        LoggerInterface $logger,
        AdapterHelper $adapterHelper
    ) {
        $this->responseFactory = $responseFactory;
        $this->mapper = $mapper;
        $this->adapterHelper = $adapterHelper;
        $this->algoliaAggregationBuilder = $aggregationBuilder;
        $this->queryContainerFactory = $queryContainerFactory;

        parent::__construct(
            $connectionManager,
            $mapper,
            $responseFactory,
            $aggregationBuilder,
            $queryContainerFactory,
            $logger
        );
    }

    /**
     * {@inheritdoc}
     */
    public function query(RequestInterface $request): QueryResponse
    {
        if (!$this->adapterHelper->isAllowed()
            || !(
                $this->adapterHelper->isSearch() ||
                $this->adapterHelper->isReplaceCategory() ||
                $this->adapterHelper->isReplaceAdvancedSearch() ||
                $this->adapterHelper->isLandingPage()
            )
        ) {
            return $this->nativeQuery($request);
        }

        $query = $this->mapper->buildQuery($request);
        $this->algoliaAggregationBuilder->setQuery($this->queryContainerFactory->create(['query' => $query]));

        $rawResponse = [];
        $totalHits = 0;
        $table = null;

        try {
            // If instant search is on, do not make a search query unless SEO request is set to 'Yes'
            if (!$this->adapterHelper->isInstantEnabled() || $this->adapterHelper->makeSeoRequest()) {
                list($documents, $totalHits, $facetsFromAlgolia) = $this->adapterHelper->getDocumentsFromAlgolia();
                $rawResponse = $this->transformResponseForElastic($rawResponse);
            }
        } catch (AlgoliaConnectionException $e) {
            return $this->nativeQuery($request);
        }

        $this->algoliaAggregationBuilder->setFacets($facetsFromAlgolia);
        $aggregations = $this->algoliaAggregationBuilder->build($request, $rawResponse);

        $response = [
            'documents' => $rawResponse,
            'aggregations' => $aggregations,
            'total' => $totalHits,
        ];

        return $this->responseFactory->create($response);
    }

    private function nativeQuery($request)
    {
        return parent::query($request);
    }

    /**
     * @param array $rawResponse
     * @return array
     */
    private function transformResponseForElastic(array $rawResponse)
    {
        if (count($rawResponse) > 0) {
            foreach ($rawResponse as &$hit) {
                $hit['_id'] = $hit['entity_id'];
            }
        }

        $rawResponse['hits'] = ['hits' => $rawResponse];

        return $rawResponse;
    }
}
