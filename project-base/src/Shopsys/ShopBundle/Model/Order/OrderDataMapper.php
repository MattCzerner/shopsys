<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\FrontOrderData as BaseFrontOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderDataMapper as BaseOrderDataMapper;

/**
 * @property \Shopsys\ShopBundle\Model\Order\OrderDataFactory $orderDataFactory
 */
class OrderDataMapper extends BaseOrderDataMapper
{
    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderDataFactory $orderDataFactory
     */
    public function __construct(OrderDataFactoryInterface $orderDataFactory)
    {
        parent::__construct($orderDataFactory);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\FrontOrderData $frontOrderData
     * @return \Shopsys\ShopBundle\Model\Order\OrderData
     */
    public function getOrderDataFromFrontOrderData(BaseFrontOrderData $frontOrderData)
    {
        /** @var \Shopsys\ShopBundle\Model\Order\OrderData $orderData */
        $orderData = parent::getOrderDataFromFrontOrderData($frontOrderData);

        return $orderData;
    }
}
