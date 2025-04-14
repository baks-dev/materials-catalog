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

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier\AllMaterialsIdentifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group materials-catalog
 */
#[When(env: 'test')]
class AllMaterialsIdentifierRepositoryTest extends KernelTestCase
{
    private static array|false $data;

    public static function setUpBeforeClass(): void
    {
        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier->findAll();


        foreach($result as $data)
        {
            self::assertTrue(array_key_exists('material_id', $data));
            self::assertTrue(array_key_exists('material_event', $data));
            self::assertTrue(array_key_exists('offer_id', $data));
            self::assertTrue(array_key_exists('offer_const', $data));
            self::assertTrue(array_key_exists('variation_id', $data));
            self::assertTrue(array_key_exists('variation_const', $data));
            self::assertTrue(array_key_exists('modification_id', $data));
            self::assertTrue(array_key_exists('modification_const', $data));

            self::$data = $data;

            break;
        }


        self::assertTrue(true);
    }

    public function testMaterialCase(): void
    {
        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forMaterial(self::$data['material_id'])
            ->findAll();

        foreach($result as $data)
        {
            self::assertTrue(isset($data['material_id']));
            self::assertEquals(self::$data['material_id'], $data['material_id']);

        }

        self::assertTrue(true);
    }

    public function testOfferCase(): void
    {

        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forOfferConst(self::$data['offer_const'])
            ->findAll();

        foreach($result as $data)
        {
            self::assertTrue(isset($data['material_id']));
            self::assertEquals(self::$data['material_id'], $data['material_id']);

            self::assertTrue(isset($data['offer_const']));
            self::assertEquals(self::$data['offer_const'], $data['offer_const']);
        }


        self::assertTrue(true);
    }


    public function testVariationCase(): void
    {

        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forOfferConst(self::$data['offer_const'])
            ->forVariationConst(self::$data['variation_const'])
            ->findAll();

        foreach($result as $data)
        {
            self::assertEquals(self::$data['material_id'], $data['material_id']);
            self::assertEquals(self::$data['offer_const'], $data['offer_const']);
            self::assertEquals(self::$data['variation_const'], $data['variation_const']);
        }


        self::assertTrue(true);
    }


    public function testModificationCase(): void
    {
        /** @var AllMaterialsIdentifierInterface $AllMaterialsIdentifier */
        $AllMaterialsIdentifier = self::getContainer()->get(AllMaterialsIdentifierInterface::class);
        $result = $AllMaterialsIdentifier
            ->forOfferConst(self::$data['offer_const'])
            ->forVariationConst(self::$data['variation_const'])
            ->forModificationConst(self::$data['modification_const'])
            ->findAll();

        foreach($result as $data)
        {
            self::assertEquals(self::$data['material_id'], $data['material_id']);
            self::assertEquals(self::$data['offer_const'], $data['offer_const']);
            self::assertEquals(self::$data['variation_const'], $data['variation_const']);
            self::assertEquals(self::$data['modification_const'], $data['modification_const']);
        }


        self::assertTrue(true);
    }

}
