<?php

namespace Tests\Intaro\RetailCrm\Component\Builder\Api;

use Bitrix\Main\Type\DateTime;
use Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Bitrix\User;
use PHPUnit\Framework\TestCase;

class CustomerBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        $class = new \ReflectionClass(ConfigProvider::class);
        $property = $class->getProperty('contragentTypes');
        $property->setAccessible(true);
        $property->setValue([
            'individual' => 'individual'
        ]);
    }

    /**
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     * @var User $entity
     * @dataProvider userData
     */
    public function testBuild($entity): void
    {
        $this->assertTrue($entity instanceof User);

        $builder = new CustomerBuilder();
        $result = $builder
            ->setPersonTypeId('individual')
            ->setUser($entity)
            ->build()
            ->getResult();

        $this->assertTrue($result instanceof Customer);
        $this->assertEquals($entity->getId(), $result->externalId);
    }

    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\User[]
     */
    public function userData()
    {
        $entity = new User();
        $entity->setId(21);
        $entity->setEmail('vovka@narod.ru');
        $entity->setDateRegister(DateTime::createFromPhp(new \DateTime()));
        $entity->setName('First');
        $entity->setLastName('Last');
        $entity->setSecondName('Second');
        $entity->setPersonalPhone('88005553535');
        $entity->setWorkPhone('88005553536');
        $entity->setPersonalCity('city');
        $entity->setPersonalStreet('street');
        $entity->setPersonalZip('344000');

        return [$entity];
    }
}
