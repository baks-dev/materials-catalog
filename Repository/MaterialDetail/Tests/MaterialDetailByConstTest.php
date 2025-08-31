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

namespace BaksDev\Materials\Catalog\Repository\MaterialDetail\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Repository\MaterialDetail\MaterialDetailByConstInterface;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[When(env: 'test')]
#[Group('materials-catalog')]
class MaterialDetailByConstTest extends KernelTestCase
{
    private static array|false $result;

    public static function setUpBeforeClass(): void
    {
        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        $DBALQueryBuilder = self::getContainer()->get(DBALQueryBuilder::class);

        /** @var DBALQueryBuilder $dbal */
        $dbal = $DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('material.id AS id')
            ->addSelect('material.event AS event')
            ->from(Material::class, 'material');

        $dbal
            ->addSelect('offer.id AS offer')
            ->addSelect('offer.const AS offer_const')
            ->leftJoin('material', MaterialOffer::class, 'offer', 'offer.event = material.event');

        $dbal
            ->addSelect('variation.id AS variation')
            ->addSelect('variation.const AS variation_const')
            ->leftJoin('offer', MaterialVariation::class, 'variation', 'variation.offer = offer.id');


        $dbal
            ->addSelect('modification.id AS modification')
            ->addSelect('modification.const AS modification_const')
            ->leftJoin('variation', MaterialModification::class, 'modification', 'modification.variation = variation.id');


        $dbal->setMaxResults(1);

        self::$result = $dbal->fetchAssociative();
    }


    public function testUseCase(): void
    {

        if(false === self::$result)
        {
            self::assertFalse(self::$result);
            return;
        }



        /** @var MaterialDetailByConstInterface $OneMaterialDetailByConst */
        $OneMaterialDetailByConst = self::getContainer()->get(MaterialDetailByConstInterface::class);

        $current = $OneMaterialDetailByConst
            ->material(self::$result['id'])
            ->offerConst(self::$result['offer_const'])
            ->variationConst(self::$result['variation_const'])
            ->modificationConst(self::$result['modification_const'])
            ->find();


        $array_keys = [
            "id",
            "event",
            "material_name",

            "material_offer_uid",
            "material_offer_const",
            "material_offer_value",
            "material_offer_reference",
            "material_offer_name",

            "material_variation_uid",
            "material_variation_const",
            "material_variation_value",
            "material_variation_reference",
            "material_variation_name",

            "material_modification_uid",
            "material_modification_const",
            "material_modification_value",
            "material_modification_reference",
            "material_modification_name",

            "material_article",
            "material_image",
            "material_image_ext",
            "material_image_cdn",

            "category_name",

            "material_quantity",
            "material_price",
            "material_currency",

        ];


        foreach($current as $key => $value)
        {
            self::assertTrue(in_array($key, $array_keys), sprintf('Появился новый ключ %s', $key));
        }

        foreach($array_keys as $key)
        {
            self::assertTrue(array_key_exists($key, $current), sprintf('Неизвестный новый ключ %s', $key));
        }

    }
}