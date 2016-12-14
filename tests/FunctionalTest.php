<?php

/*
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Dunglas\DoctrineJsonOdm\tests;

use Dunglas\DoctrineJsonOdm\Tests\Fixtures\TestBundle\Entity\Attribute;
use Dunglas\DoctrineJsonOdm\Tests\Fixtures\TestBundle\Entity\Bar;
use Dunglas\DoctrineJsonOdm\Tests\Fixtures\TestBundle\Entity\Baz;
use Dunglas\DoctrineJsonOdm\Tests\Fixtures\TestBundle\Entity\Foo;
use Dunglas\DoctrineJsonOdm\Tests\Fixtures\TestBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\StringInput;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class FunctionalTest extends KernelTestCase
{
    /**
     * @var Application
     */
    private $application;

    protected function setUp()
    {
        $this->bootKernel();

        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);

        $this->runCommand('doctrine:schema:drop --force');
        $this->runCommand('doctrine:schema:create');
    }

    private function runCommand($command)
    {
        return $this->application->run(new StringInput($command.' --no-interaction --quiet'));
    }

    public function testStoreAndRetrieveDocument()
    {
        $attribute1 = new Attribute();
        $attribute1->key = 'foo';
        $attribute1->value = 'bar';

        $attribute2 = new Attribute();
        $attribute2->key = 'weights';
        $attribute2->value = [34, 67];

        $attributes = [$attribute1, $attribute2];

        $product = new Product();
        $product->name = 'My product';
        $product->attributes = $attributes;

        $manager = self::$kernel->getContainer()->get('doctrine')->getManagerForClass(Product::getClass());
        $manager->persist($product);
        $manager->flush();

        $manager->clear();

        $retrievedProduct = $manager->find(Product::getClass(), $product->id);
        $this->assertEquals($attributes, $retrievedProduct->attributes);
    }

    public function testStoreAndRetrieveDocumentsOfVariousTypes()
    {
        $bar = new Bar();
        $bar->setTitle('Bar');
        $bar->setWeight(12);

        $baz = new Baz();
        $baz->setName('Baz');
        $baz->setSize(7);

        $misc = [$bar, $baz];

        $foo = new Foo();
        $foo->setName('Foo');
        $foo->setMisc($misc);

        $manager = self::$kernel->getContainer()->get('doctrine')->getManagerForClass(Foo::getClass());
        $manager->persist($foo);
        $manager->flush();

        $manager->clear();

        $foo = $manager->find(Foo::getClass(), $foo->getId());
        $this->assertEquals($misc, $foo->getMisc());
    }

    public function testNestedObjects()
    {
        $attribute = new Attribute();
        $attribute->key = 'nested';
        $attribute->value = 'bar';

        $attributeParent = new Attribute();
        $attributeParent->key = 'parent';
        $attributeParent->value = [[$attribute]];

        $misc = [$attributeParent];

        $foo = new Foo();
        $foo->setName('foo');
        $foo->setMisc($misc);

        $manager = self::$kernel->getContainer()->get('doctrine')->getManagerForClass(Foo::getClass());
        $manager->persist($foo);
        $manager->flush();

        $manager->clear();

        $foo = $manager->find(Foo::getClass(), $foo->getId());
        $this->assertEquals($misc, $foo->getMisc());
    }
}
