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

namespace BaksDev\Materials\Catalog\Repository\MaterialVariationChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Trans\CategoryMaterialOffersTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Trans\CategoryMaterialVariationTrans;
use Generator;

final class MaterialVariationChoiceRepository implements MaterialVariationChoiceInterface
{
    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /**
     * Метод возвращает все постоянные идентификаторы CONST множественных вариантов торговых предложений сырья
     */
    public function fetchMaterialVariationByOfferConst(MaterialOfferConst|string $const): Generator
    {

        if(is_string($const))
        {
            $const = new MaterialOfferConst($const);
        }


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(MaterialOffer::class, 'offer')
            ->where('offer.const = :const')
            ->setParameter('const', $const, MaterialOfferConst::TYPE);

        $dbal->join(
            'offer',
            Material::class,
            'material',
            'material.event = offer.event'
        );

        $dbal->join(
            'offer',
            MaterialVariation::class,
            'variation',
            'variation.offer = offer.id'
        );

        // Тип торгового предложения

        $dbal->join(
            'variation',
            CategoryMaterialVariation::class,
            'category_variation',
            'category_variation.id = variation.category_variation'
        );

        $dbal->leftJoin(
            'category_variation',
            CategoryMaterialVariationTrans::class,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
        );


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('variation.const AS value');
        $dbal->addSelect('variation.value AS attr');

        $dbal->addSelect('category_variation_trans.name AS option');
        $dbal->addSelect('category_variation.reference AS property');


        $dbal->orderBy('variation.value');

        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAllHydrate(MaterialVariationConst::class);


    }


    public function fetchMaterialVariationByOffer(MaterialOfferUid $offer): ?array
    {
        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $select = sprintf('new %s(
            variation.id, 
            variation.value, 
            trans.name, 
            category_variation.reference
        )', MaterialVariationUid::class);

        $qb->select($select);

        $qb->from(MaterialOffer::class, 'offer');


        $qb->join(
            Material::class,
            'material',
            'WITH',
            'material.event = offer.event'
        );


        $qb->join(
            MaterialVariation::class,
            'variation',
            'WITH',
            'variation.offer = offer.id'
        );

        // Тип торгового предложения

        $qb->join(
            CategoryMaterialVariation::class,
            'category_variation',
            'WITH',
            'category_variation.id = variation.categoryVariation'
        );

        $qb->leftJoin(
            CategoryMaterialVariationTrans::class,
            'trans',
            'WITH',
            'trans.variation = category_variation.id AND trans.local = :local'
        );

        $qb->where('offer.id = :offer');

        $qb->setParameter('offer', $offer, MaterialOfferUid::TYPE);

        /* Кешируем результат ORM */
        return $qb->enableCache('materials-catalog', 86400)->getResult();

    }


    /**
     * Метод возвращает все идентификаторы множественных вариантов торговых предложений сырья имеющиеся в доступе
     */
    public function fetchMaterialVariationExistsByOffer(MaterialOfferUid|string $offer): Generator
    {
        if(is_string($offer))
        {
            $offer = new MaterialOfferUid($offer);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(MaterialOffer::class, 'material_offer')
            ->where('material_offer.id = :offer')
            ->setParameter('offer', $offer, MaterialOfferUid::TYPE);

        $dbal->join(
            'material_offer',
            Material::class,
            'material',
            'material.event = material_offer.event'
        );

        $dbal->join(
            'material_offer',
            MaterialVariation::class,
            'material_variation',
            'material_variation.offer = material_offer.id'
        );

        // Тип множественного варианта предложения

        $dbal->join(
            'material_variation',
            CategoryMaterialVariation::class,
            'category_variation',
            'category_variation.id = material_variation.category_variation'
        );

        $dbal->leftJoin(
            'category_variation',
            CategoryMaterialVariationTrans::class,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
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


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('material_variation.id AS value');

        $dbal->addSelect('

            CASE
               WHEN SUM(material_modification_quantity.quantity - material_modification_quantity.reserve) > 0
               THEN SUM(material_modification_quantity.quantity - material_modification_quantity.reserve)

               WHEN SUM(material_variation_quantity.quantity - material_variation_quantity.reserve) > 0
               THEN SUM(material_variation_quantity.quantity - material_variation_quantity.reserve)

               ELSE 0
            END

        AS option');

        $dbal->andWhere('
            material_modification_quantity.quantity > 0 OR 
            material_variation_quantity.quantity > 0 
        ');

        $dbal->addSelect('category_variation_trans.name AS property');
        $dbal->addSelect('category_variation.reference AS characteristic');

        $dbal->allGroupByExclude();

        return $dbal->fetchAllHydrate(MaterialVariationUid::class);


    }


}
