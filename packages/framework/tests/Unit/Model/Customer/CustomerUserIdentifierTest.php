<?php

namespace Tests\FrameworkBundle\Unit\Model\Customer;

use PHPUnit\Framework\TestCase;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier;
use Shopsys\FrameworkBundle\Model\Customer\Exception\EmptyCustomerUserIdentifierException;

class CustomerUserIdentifierTest extends TestCase
{
    public function testCannotCreateIdentifierForEmptyCartIdentifierAndNullUser()
    {
        $cartIdentifier = '';
        $customerUser = null;

        $this->expectException(EmptyCustomerUserIdentifierException::class);
        new CustomerUserIdentifier($cartIdentifier, $customerUser);
    }
}
