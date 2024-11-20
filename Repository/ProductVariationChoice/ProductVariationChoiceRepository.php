<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Catalog\Repository\ProductVariationChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;
use Generator;

final class ProductVariationChoiceRepository implements ProductVariationChoiceInterface
{
    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /**
     * Метод возвращает все постоянные идентификаторы CONST множественных вариантов торговых предложений продукта
     */
    public function fetchProductVariationByOfferConst(ProductOfferConst|string $const): Generator
    {

        if(is_string($const))
        {
            $const = new ProductOfferConst($const);
        }


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(ProductOffer::class, 'offer')
            ->where('offer.const = :const')
            ->setParameter('const', $const, ProductOfferConst::TYPE);

        $dbal->join(
            'offer',
            Material::class,
            'product',
            'product.event = offer.event'
        );

        $dbal->join(
            'offer',
            ProductVariation::class,
            'variation',
            'variation.offer = offer.id'
        );

        // Тип торгового предложения

        $dbal->join(
            'variation',
            CategoryProductVariation::class,
            'category_variation',
            'category_variation.id = variation.category_variation'
        );

        $dbal->leftJoin(
            'category_variation',
            CategoryProductVariationTrans::class,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
        );


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('variation.const AS value');
        $dbal->addSelect('variation.value AS attr');

        $dbal->addSelect('category_variation_trans.name AS option');
        $dbal->addSelect('category_variation.reference AS property');

        $dbal->addSelect('variation.postfix AS characteristic');


        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAllHydrate(ProductVariationConst::class);


    }


    public function fetchProductVariationByOffer(ProductOfferUid $offer): ?array
    {
        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $select = sprintf('new %s(
            variation.id, 
            variation.value, 
            trans.name, 
            category_variation.reference
        )', ProductVariationUid::class);

        $qb->select($select);

        $qb->from(ProductOffer::class, 'offer');


        $qb->join(
            Material::class,
            'product',
            'WITH',
            'product.event = offer.event'
        );


        $qb->join(
            ProductVariation::class,
            'variation',
            'WITH',
            'variation.offer = offer.id'
        );

        // Тип торгового предложения

        $qb->join(
            CategoryProductVariation::class,
            'category_variation',
            'WITH',
            'category_variation.id = variation.categoryVariation'
        );

        $qb->leftJoin(
            CategoryProductVariationTrans::class,
            'trans',
            'WITH',
            'trans.variation = category_variation.id AND trans.local = :local'
        );

        $qb->where('offer.id = :offer');

        $qb->setParameter('offer', $offer, ProductOfferUid::TYPE);

        /* Кешируем результат ORM */
        return $qb->enableCache('materials-catalog', 86400)->getResult();

    }


    /**
     * Метод возвращает все идентификаторы множественных вариантов торговых предложений продукта имеющиеся в доступе
     */
    public function fetchProductVariationExistsByOffer(ProductOfferUid|string $offer): Generator
    {
        if(is_string($offer))
        {
            $offer = new ProductOfferUid($offer);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(ProductOffer::class, 'product_offer')
            ->where('product_offer.id = :offer')
            ->setParameter('offer', $offer, ProductOfferUid::TYPE);

        $dbal->join(
            'product_offer',
            Material::class,
            'product',
            'product.event = product_offer.event'
        );

        $dbal->join(
            'product_offer',
            ProductVariation::class,
            'product_variation',
            'product_variation.offer = product_offer.id'
        );

        // Тип множественного варианта предложения

        $dbal->join(
            'product_variation',
            CategoryProductVariation::class,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );

        $dbal->leftJoin(
            'category_variation',
            CategoryProductVariationTrans::class,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
        );


        $dbal->leftJoin(
            'product_variation',
            ProductModification::class,
            'product_modification',
            'product_modification.variation = product_variation.id'
        );

        /**
         * Quantity
         */

        $dbal->leftJoin(
            'product_offer',
            ProductOfferQuantity::class,
            'product_offer_quantity',
            'product_offer_quantity.offer = product_offer.id'
        );

        $dbal->leftJoin(
            'product_variation',
            ProductVariationQuantity::class,
            'product_variation_quantity',
            'product_variation_quantity.variation = product_variation.id'
        );

        $dbal->leftJoin(
            'product_modification',
            ProductModificationQuantity::class,
            'product_modification_quantity',
            'product_modification_quantity.modification = product_modification.id'
        );


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('product_variation.id AS value');
        $dbal->addSelect("CONCAT(product_variation.value, ' ', product_variation.postfix) AS attr");

        $dbal->addSelect('

            CASE
               WHEN SUM(product_modification_quantity.quantity - product_modification_quantity.reserve) > 0
               THEN SUM(product_modification_quantity.quantity - product_modification_quantity.reserve)

               WHEN SUM(product_variation_quantity.quantity - product_variation_quantity.reserve) > 0
               THEN SUM(product_variation_quantity.quantity - product_variation_quantity.reserve)

               ELSE 0
            END

        AS option');

        $dbal->andWhere('
            product_modification_quantity.quantity > 0 OR 
            product_variation_quantity.quantity > 0 
        ');

        $dbal->addSelect('category_variation_trans.name AS property');
        $dbal->addSelect('category_variation.reference AS characteristic');

        $dbal->allGroupByExclude();

        return $dbal->fetchAllHydrate(ProductVariationUid::class);


    }


}
