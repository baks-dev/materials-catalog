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

namespace BaksDev\Materials\Catalog\Repository\UpdateMaterialQuantity\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Repository\UpdateMaterialQuantity\AddMaterialQuantityInterface;
use BaksDev\Materials\Catalog\Repository\UpdateMaterialQuantity\SubMaterialQuantityInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('materials-catalog')]
class AddSubMaterialQuantityRepositoryTest extends KernelTestCase
{
    private static array|false $result;

    public static function getData(): void
    {
        $DBALQueryBuilder = self::getContainer()->get(DBALQueryBuilder::class);

        /** @var DBALQueryBuilder $dbal */
        $dbal = $DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('material.event AS event')
            ->from(Material::class, 'material');


        $dbal
            ->addSelect('quantity.quantity')
            ->addSelect('quantity.reserve')
            ->leftJoin(
                'material',
                MaterialPrice::class,
                'quantity',
                'quantity.event = material.event'
            );

        $dbal
            ->addSelect('offer.id AS offer')
            ->leftJoin('material', MaterialOffer::class, 'offer', 'offer.event = material.event');

        $dbal
            ->addSelect('offer_quantity.quantity AS offer_quantity')
            ->addSelect('offer_quantity.reserve AS offer_reserve')
            ->leftJoin('offer', MaterialOfferQuantity::class, 'offer_quantity', 'offer_quantity.offer = offer.id');

        $dbal
            ->addSelect('variation.id AS variation')
            ->leftJoin('offer', MaterialVariation::class, 'variation', 'variation.offer = offer.id');

        $dbal
            ->addSelect('variation_quantity.quantity AS variation_quantity')
            ->addSelect('variation_quantity.reserve AS variation_reserve')
            ->leftJoin('variation', MaterialsVariationQuantity::class, 'variation_quantity', 'variation_quantity.variation = variation.id');


        $dbal
            ->addSelect('modification.id AS modification')
            ->leftJoin('variation', MaterialModification::class, 'modification', 'modification.variation = variation.id');

        $dbal
            ->addSelect('modification_quantity.quantity AS modification_quantity')
            ->addSelect('modification_quantity.reserve AS modification_reserve')
            ->leftJoin('modification', MaterialModificationQuantity::class, 'modification_quantity', 'modification_quantity.modification = modification.id');


        $dbal->setMaxResults(1);

        self::$result = $dbal->fetchAssociative();

    }

    /*
    "event" => "0191140d-eaa3-7f16-9218-f8b05c20266f"
    "quantity" => 0
    "reserve" => 0
    "offer" => "0191140d-eaa4-77aa-97c5-873d13abeb06"
    "offer_quantity" => 0
    "offer_reserve" => 0
    "variation" => "0191140d-eaa4-77aa-97c5-873d14871960"
    "variation_quantity" => 0
    "variation_reserve" => 0
    "modification" => "0191140d-eaa4-77aa-97c5-873d148f68f8"
    "modification_quantity" => 10
    "modification_reserve" => 4*/

    public static function testAddEventQuantity(): void
    {
        self::getData();

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->addQuantity(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();


        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['quantity'] + 1), self::$result['quantity']);
        self::assertEquals(($data['reserve']), self::$result['reserve']);

    }


    public static function testAddEventReserve(): void
    {
        self::getData();

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->addReserve(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();


        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['quantity']), self::$result['quantity']);
        self::assertEquals(($data['reserve'] + 1), self::$result['reserve']);

    }


    public static function testSubEventCase(): void
    {
        //self::getData();

        /** @var SubMaterialQuantityInterface $SubMaterialQuantityInterface */
        $SubMaterialQuantityInterface = self::getContainer()->get(SubMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $SubMaterialQuantityInterface
            ->forEvent($data['event'])
            ->subQuantity(1)
            ->subReserve(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['quantity'] - 1), self::$result['quantity']);
        self::assertEquals(($data['reserve'] - 1), self::$result['reserve']);
    }


    /** Offer */


    public static function testAddOfferQuantity(): void
    {
        self::getData();

        if(!self::$result['offer'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->forOffer(self::$result['offer'])
            ->addQuantity(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['offer_quantity'] + 1), self::$result['offer_quantity']);
        self::assertEquals(($data['offer_reserve']), self::$result['offer_reserve']);

    }

    public static function testAddOfferReserve(): void
    {
        self::getData();

        if(!self::$result['offer'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->forOffer(self::$result['offer'])
            ->addReserve(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['offer_quantity']), self::$result['offer_quantity']);
        self::assertEquals(($data['offer_reserve'] + 1), self::$result['offer_reserve']);

    }


    public static function testSubOfferCase(): void
    {
        //self::getData();

        if(!self::$result['offer'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var SubMaterialQuantityInterface $SubMaterialQuantityInterface */
        $SubMaterialQuantityInterface = self::getContainer()->get(SubMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $SubMaterialQuantityInterface
            ->forEvent($data['event'])
            ->forOffer($data['offer'])
            ->subQuantity(1)
            ->subReserve(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['offer_quantity'] - 1), self::$result['offer_quantity']);
        self::assertEquals(($data['offer_reserve'] - 1), self::$result['offer_reserve']);
    }


    /** Variation */


    public static function testAddVariationQuantity(): void
    {
        self::getData();

        if(!self::$result['variation'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->forOffer(self::$result['offer'])
            ->forVariation(self::$result['variation'])
            ->addQuantity(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['variation_quantity'] + 1), self::$result['variation_quantity']);
        self::assertEquals(($data['variation_reserve']), self::$result['variation_reserve']);

    }

    public static function testAddVariationReserve(): void
    {
        self::getData();

        if(!self::$result['variation'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->forOffer(self::$result['offer'])
            ->forVariation(self::$result['variation'])
            ->addReserve(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();


        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);


        self::assertEquals(($data['variation_quantity']), self::$result['variation_quantity']);
        self::assertEquals(($data['variation_reserve'] + 1), self::$result['variation_reserve']);

    }


    public static function testSubVariationCase(): void
    {
        if(!self::$result['variation'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var SubMaterialQuantityInterface $SubMaterialQuantityInterface */
        $SubMaterialQuantityInterface = self::getContainer()->get(SubMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $SubMaterialQuantityInterface
            ->forEvent($data['event'])
            ->forOffer($data['offer'])
            ->forVariation($data['variation'])
            ->subQuantity(1)
            ->subReserve(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['modification_quantity'], self::$result['modification_quantity']);
        self::assertEquals($data['modification_reserve'], self::$result['modification_reserve']);

        self::assertEquals(($data['variation_quantity'] - 1), self::$result['variation_quantity']);
        self::assertEquals(($data['variation_reserve'] - 1), self::$result['variation_reserve']);

    }


    /** Modification */


    public static function testAddModificationQuantity(): void
    {
        self::getData();

        if(!self::$result['modification'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;


        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->forOffer(self::$result['offer'])
            ->forVariation(self::$result['variation'])
            ->forModification(self::$result['modification'])
            ->addQuantity(1)
            ->update();

        self::assertEquals(1, $int);


        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);

        self::assertEquals(($data['modification_quantity'] + 1), self::$result['modification_quantity']);
        self::assertEquals(($data['modification_reserve']), self::$result['modification_reserve']);


    }

    public static function testAddModificationReserve(): void
    {
        self::getData();

        if(!self::$result['modification'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var AddMaterialQuantityInterface $AddMaterialQuantityInterface */
        $AddMaterialQuantityInterface = self::getContainer()->get(AddMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $AddMaterialQuantityInterface
            ->forEvent(self::$result['event'])
            ->forOffer(self::$result['offer'])
            ->forVariation(self::$result['variation'])
            ->forModification(self::$result['modification'])
            ->addReserve(1)
            ->update();


        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);

        self::assertEquals(($data['modification_quantity']), self::$result['modification_quantity']);
        self::assertEquals(($data['modification_reserve'] + 1), self::$result['modification_reserve']);

    }


    public static function testSubModificationCase(): void
    {
        //self::getData();

        if(!self::$result['modification'])
        {
            self::assertTrue(true);
            return;
        }

        /** @var SubMaterialQuantityInterface $SubMaterialQuantityInterface */
        $SubMaterialQuantityInterface = self::getContainer()->get(SubMaterialQuantityInterface::class);

        $data = self::$result;

        $int = $SubMaterialQuantityInterface
            ->forEvent($data['event'])
            ->forOffer($data['offer'])
            ->forVariation($data['variation'])
            ->forModification($data['modification'])
            ->subQuantity(1)
            ->subReserve(1)
            ->update();

        self::assertEquals(1, $int);

        self::getData();

        self::assertEquals($data['quantity'], self::$result['quantity']);
        self::assertEquals($data['reserve'], self::$result['reserve']);
        self::assertEquals($data['offer_quantity'], self::$result['offer_quantity']);
        self::assertEquals($data['offer_reserve'], self::$result['offer_reserve']);
        self::assertEquals($data['variation_quantity'], self::$result['variation_quantity']);
        self::assertEquals($data['variation_reserve'], self::$result['variation_reserve']);

        self::assertEquals(($data['modification_quantity'] - 1), self::$result['modification_quantity']);
        self::assertEquals(($data['modification_reserve'] - 1), self::$result['modification_reserve']);
    }

}
