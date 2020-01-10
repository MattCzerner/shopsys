<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\Model\Resolver\Products;

use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductCollectionFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductResolverMap extends ResolverMap
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Collection\ProductCollectionFacade
     */
    protected $productCollectionFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\Collection\ProductCollectionFacade $productCollectionFacade
     */
    public function __construct(Domain $domain, ProductCollectionFacade $productCollectionFacade)
    {
        $this->domain = $domain;
        $this->productCollectionFacade = $productCollectionFacade;
    }

    /**
     * @return array
     */
    protected function map(): array
    {
        return [
            'Product' => [
                self::RESOLVE_TYPE => function ($data) {
                    $isMainVariant = $data instanceof Product ? $data->isMainVariant() : $data['is_main_variant'];
                    $isVariant = $data instanceof Product ? $data->isVariant() : $data['main_variant'] !== null;

                    if ($isMainVariant) {
                        return 'MainVariant';
                    } elseif ($isVariant) {
                        return 'Variant';
                    } else {
                        return 'RegularProduct';
                    }
                },
            ],
            'RegularProduct' => $this->mapProduct(),
            'Variant' => $this->mapProduct(),
            'MainVariant' => $this->mapProduct(),
        ];
    }

    /**
     * @return array
     */
    protected function mapProduct(): array
    {
        return [
            'shortDescription' => function ($data) {
                return $data instanceof Product ? $data->getShortDescription($this->domain->getId()) : $data['short_description'];
            },
            'link' => function ($data) {
                $productId = $data instanceof Product ? $data->getId() : $data['id'];
                return $this->getProductLink($productId);
            },
            'categories' => function ($data) {
                return $data instanceof Product ? $data->getCategoriesIndexedByDomainId()[$this->domain->getId()] : $data['categories'];
            },
        ];
    }

    /**
     * @param int $productId
     * @return string
     */
    protected function getProductLink(int $productId): string
    {
        $absoluteUrlsIndexedByProductId = $this->productCollectionFacade->getAbsoluteUrlsIndexedByProductId([$productId], $this->domain->getCurrentDomainConfig());

        return $absoluteUrlsIndexedByProductId[$productId];
    }
}
