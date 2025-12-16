<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier\Tests;

use BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier\AllMaterialsIdentifierInterface;
use BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier\AllMaterialsIdentifierResult;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('materials-catalog')]
#[Group('materials-catalog-repository')]
class AllMaterialsIdentifierRepositoryTest extends KernelTestCase
{
    private static AllMaterialsIdentifierResult|false $data;

    public static function setUpBeforeClass(): void
    {
        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier->findAllResult();

        foreach($result as $material)
        {
            self::assertInstanceOf(AllMaterialsIdentifierResult::class, $material);

            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(AllMaterialsIdentifierResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($material);
                    //dump($data);
                }
            }

            self::$data = $material;

            break;
        }


        self::assertTrue(true);
    }

    public function testMaterialCase(): void
    {
        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forMaterial(self::$data->getMaterialId())
            ->findAllResult();

        foreach($result as $material)
        {
            self::assertEquals(self::$data->getMaterialId(), $material->getMaterialId());

        }

        self::assertTrue(true);
    }

    public function testOfferCase(): void
    {
        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forOfferConst(self::$data->getOfferConst())
            ->findAllResult();

        foreach($result as $material)
        {
            self::assertEquals(self::$data->getMaterialId(), $material->getMaterialId());
            self::assertEquals(self::$data->getOfferConst(), $material->getOfferConst());

            break;
        }


        self::assertTrue(true);
    }


    public function testVariationCase(): void
    {

        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forOfferConst(self::$data->getOfferConst())
            ->forVariationConst(self::$data->getVariationConst())
            ->findAllResult();

        foreach($result as $material)
        {
            self::assertEquals(self::$data->getMaterialId(), $material->getMaterialId());
            self::assertEquals(self::$data->getOfferConst(), $material->getOfferConst());
            self::assertEquals(self::$data->getVariationConst(), $material->getVariationConst());

            break;
        }


        self::assertTrue(true);
    }


    public function testModificationCase(): void
    {
        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forOfferConst(self::$data->getOfferConst())
            ->forVariationConst(self::$data->getVariationConst())
            ->forModificationConst(self::$data->getModificationConst())
            ->findAllResult();

        foreach($result as $material)
        {
            self::assertEquals(self::$data->getMaterialId(), $material->getMaterialId());
            self::assertEquals(self::$data->getOfferConst(), $material->getOfferConst());
            self::assertEquals(self::$data->getVariationConst(), $material->getVariationConst());
            self::assertEquals(self::$data->getModificationConst(), $material->getModificationConst());

            break;
        }

        self::assertTrue(true);
    }

}
