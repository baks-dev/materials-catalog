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

namespace BaksDev\Materials\Catalog\Repository\MaterialOfferChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Trans\CategoryMaterialOffersTrans;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use Generator;

final readonly class MaterialOfferChoiceRepository implements MaterialOfferChoiceInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает все постоянные идентификаторы CONST торговых предложений сырья
     */
    public function findByMaterial(MaterialUid|string $material): Generator
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(Material::class, 'material')
            ->where('material.id = :material')
            ->setParameter('material', $material, MaterialUid::TYPE);


        $dbal
            ->join(
                'material',
                MaterialOffer::class,
                'offer',
                'offer.event = material.event'
            );


        $dbal
            ->join(
                'offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = offer.category_offer'
            );


        $dbal
            ->leftJoin(
                'category_offer',
                CategoryMaterialOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('offer.const AS value');
        $dbal->addSelect("offer.value AS attr");

        $dbal->addSelect('category_offer_trans.name AS option');
        $dbal->addSelect('category_offer.reference AS property');

        $dbal->orderBy('offer.value');


        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAllHydrate(MaterialOfferConst::class);
    }


    /**
     * Метод возвращает все идентификаторы торговых предложений сырья по событию имеющиеся в доступе
     */
    public function findOnlyExistsByMaterialEvent(MaterialEventUid|string $material): Generator
    {
        if(is_string($material))
        {
            $material = new MaterialEventUid($material);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(Material::class, 'material')
            ->where('material.event = :material')
            ->setParameter('material', $material, MaterialEventUid::TYPE);


        $dbal->join(
            'material',
            MaterialOffer::class,
            'material_offer',
            'material_offer.event = material.event'
        );

        // Тип торгового предложения

        $dbal->leftJoin(
            'material_offer',
            CategoryMaterialOffers::class,
            'category_offer',
            'category_offer.id = material_offer.category_offer'
        );


        $dbal->leftJoin(
            'category_offer',
            CategoryMaterialOffersTrans::class,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        $dbal->leftJoin(
            'material_offer',
            MaterialVariation::class,
            'material_variation',
            'material_variation.offer = material_offer.id'
        );

        $dbal->leftJoin(
            'material_variation',
            MaterialModification::class,
            'material_modification',
            'material_modification.variation = material_variation.id'
        );

        /**
         * Quantity
         */

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        $dbal->leftJoin(
            'material_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_modification.id'
        );


        //        $select = sprintf('new %s(
        //            offer.id,
        //            offer.value,
        //            trans.name,
        //            category_offer.reference
        //        )', MaterialOfferUid::class);
        //
        //        $dbal->select($select);

        $dbal->addSelect('material_offer.id AS value');


        $dbal->addSelect('

            CASE
               WHEN SUM(material_modification_quantity.quantity - material_modification_quantity.reserve) > 0
               THEN SUM(material_modification_quantity.quantity - material_modification_quantity.reserve)

               WHEN SUM(material_variation_quantity.quantity - material_variation_quantity.reserve) > 0
               THEN SUM(material_variation_quantity.quantity - material_variation_quantity.reserve)

               WHEN SUM(material_offer_quantity.quantity - material_offer_quantity.reserve) > 0
               THEN SUM(material_offer_quantity.quantity - material_offer_quantity.reserve)

               ELSE 0
            END

        AS option');

        $dbal->andWhere('
            material_modification_quantity.quantity > 0 OR 
            material_variation_quantity.quantity > 0 OR 
            material_offer_quantity.quantity > 0 
        ');

        /** Свойства конструктора объекта гидрации */
        $dbal->addSelect('category_offer_trans.name AS property');
        $dbal->addSelect('category_offer.reference AS characteristic');

        $dbal->allGroupByExclude();

        return $dbal->fetchAllHydrate(MaterialOfferUid::class);


    }

}
