<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Model\Product\Search;

use Doctrine\ORM\QueryBuilder;
use Elasticsearch\Client;
use Shopsys\FrameworkBundle\Component\Elasticsearch\ElasticsearchStructureManager;

class ProductElasticsearchRepository
{
    public const ELASTICSEARCH_INDEX = 'product';

    /**
     * @var string
     */
    protected $indexPrefix;

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var int[][][]
     */
    protected $foundProductIdsCache = [];

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchConverter
     */
    protected $productElasticsearchConverter;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Elasticsearch\ElasticsearchStructureManager
     */
    protected $elasticsearchStructureManager;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Search\FilterQueryFactory
     */
    protected $filterQueryFactory;

    /**
     * @param string $indexPrefix
     * @param \Elasticsearch\Client $client
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchConverter $productElasticsearchConverter
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\ElasticsearchStructureManager $elasticsearchStructureManager
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\FilterQueryFactory $filterQueryFactory
     */
    public function __construct(
        string $indexPrefix,
        Client $client,
        ProductElasticsearchConverter $productElasticsearchConverter,
        ElasticsearchStructureManager $elasticsearchStructureManager,
        FilterQueryFactory $filterQueryFactory
    ) {
        $this->indexPrefix = $indexPrefix;
        $this->client = $client;
        $this->productElasticsearchConverter = $productElasticsearchConverter;
        $this->elasticsearchStructureManager = $elasticsearchStructureManager;
        $this->filterQueryFactory = $filterQueryFactory;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $productQueryBuilder
     * @param string|null $searchText
     */
    public function filterBySearchText(QueryBuilder $productQueryBuilder, $searchText)
    {
        $productIds = $this->getFoundProductIds($productQueryBuilder, $searchText);

        if (count($productIds) > 0) {
            $productQueryBuilder->andWhere('p.id IN (:productIds)')->setParameter('productIds', $productIds);
        } else {
            $productQueryBuilder->andWhere('TRUE = FALSE');
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $productQueryBuilder
     * @param string|null $searchText
     */
    public function addRelevance(QueryBuilder $productQueryBuilder, $searchText)
    {
        $productIds = $this->getFoundProductIds($productQueryBuilder, $searchText);

        if (count($productIds) > 0) {
            $productQueryBuilder->addSelect('field(p.id, ' . implode(',', $productIds) . ') AS HIDDEN relevance');
        } else {
            $productQueryBuilder->addSelect('-1 AS HIDDEN relevance');
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $productQueryBuilder
     * @param string|null $searchText
     * @return int[]
     */
    protected function getFoundProductIds(QueryBuilder $productQueryBuilder, $searchText)
    {
        $domainId = $productQueryBuilder->getParameter('domainId')->getValue();

        if (!isset($this->foundProductIdsCache[$domainId][$searchText])) {
            $foundProductIds = $this->getProductIdsBySearchText($domainId, $searchText);

            $this->foundProductIdsCache[$domainId][$searchText] = $foundProductIds;
        }

        return $this->foundProductIdsCache[$domainId][$searchText];
    }

    /**
     * @param int $domainId
     * @param string|null $searchText
     * @return int[]
     */
    public function getProductIdsBySearchText(int $domainId, ?string $searchText): array
    {
        if ($searchText === null || $searchText === '') {
            return [];
        }

        $parameters = $this->createQuery($this->elasticsearchStructureManager->getAliasName($domainId, self::ELASTICSEARCH_INDEX), $searchText);
        $result = $this->client->search($parameters);
        return $this->extractIds($result);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\ProductIdsResult
     */
    public function getSortedProductIdsByFilterQuery(FilterQuery $filterQuery): ProductIdsResult
    {
        $result = $this->client->search($filterQuery->getQuery());

        return new ProductIdsResult($this->extractTotalCount($result), $this->extractIds($result));
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\ProductsResult
     */
    public function getSortedProductsResultByFilterQuery(FilterQuery $filterQuery): ProductsResult
    {
        $result = $this->client->search($filterQuery->getQuery());

        return new ProductsResult($this->extractTotalCount($result), $this->extractHits($result));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html
     * @param string $indexName
     * @param string $searchText
     * @return array
     */
    protected function createQuery(string $indexName, string $searchText): array
    {
        $searchText = $searchText ?? '';

        $query = $this->filterQueryFactory->create($indexName)
            ->search($searchText);
        return $query->getQuery();
    }

    /**
     * @param array $result
     * @return int[]
     */
    protected function extractIds(array $result): array
    {
        $hits = $result['hits']['hits'];
        return array_column($hits, '_id');
    }

    /**
     * @param array $result
     * @return array
     */
    protected function extractHits(array $result): array
    {
        return array_map(function ($value) {
            $data = $value['_source'];
            $data['id'] = (int)$value['_id'];

            return $this->productElasticsearchConverter->fillEmptyFields($data);
        }, $result['hits']['hits']);
    }

    /**
     * @param array $result
     * @return int
     */
    protected function extractTotalCount(array $result): int
    {
        return (int)$result['hits']['total'];
    }

    /**
     * @param int $domainId
     * @param array $data
     */
    public function bulkUpdate(int $domainId, array $data): void
    {
        $body = $this->productElasticsearchConverter->convertBulk(
            $this->elasticsearchStructureManager->getCurrentIndexName($domainId, self::ELASTICSEARCH_INDEX),
            $data
        );

        $params = [
            'body' => $body,
        ];
        $this->client->bulk($params);
    }

    /**
     * @param int $domainId
     * @param int[] $keepIds
     */
    public function deleteNotPresent(int $domainId, array $keepIds): void
    {
        $this->client->deleteByQuery([
            'index' => $this->elasticsearchStructureManager->getCurrentIndexName($domainId, self::ELASTICSEARCH_INDEX),
            'type' => '_doc',
            'body' => [
                'query' => [
                    'bool' => [
                        'must_not' => [
                            'ids' => [
                                'values' => $keepIds,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param int $domainId
     * @param int[] $deleteIds
     */
    public function delete(int $domainId, array $deleteIds): void
    {
        $this->client->deleteByQuery([
            'index' => $this->elasticsearchStructureManager->getCurrentIndexName($domainId, self::ELASTICSEARCH_INDEX),
            'type' => '_doc',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'ids' => [
                                'values' => array_values($deleteIds),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
