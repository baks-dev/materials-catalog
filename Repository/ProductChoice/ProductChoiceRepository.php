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

namespace BaksDev\Materials\Catalog\Repository\ProductChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Materials\Catalog\Entity\Active\ProductActive;
use BaksDev\Materials\Catalog\Entity\Category\ProductCategory;
use BaksDev\Materials\Catalog\Entity\Info\ProductInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Price\ProductPrice;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use Generator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductChoiceRepository implements ProductChoiceInterface
{
    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /**
     * Метод возвращает все идентификаторы продуктов (ProductUid) с названием указанной категории
     */
    public function fetchAllProduct(CategoryProductUid|false $category = false): ?array
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->from(Material::class, 'product');

        $qb->join(
            'product',
            ProductInfo::class,
            'info',
            'info.product = product.id'
        );

        if($category)
        {
            $qb
                ->join(
                    'product',
                    ProductCategory::class,
                    'category',
                    'category.event = product.event AND category.category = :category'
                )
                ->setParameter(
                    'category',
                    $category,
                    CategoryProductUid::TYPE
                );
        }


        /*$qb->leftJoin(
            'product',
            ProductOffer::class,
            'offer',
            'offer.event = product.event'
        );*/

        $qb->join(
            'product',
            ProductActive::class,
            'active',
            '
            active.event = product.event AND
            active.active = true AND
            active.active_from < NOW() AND
            ( active.active_to IS NULL OR active.active_to > NOW() )
		'
        );

        $qb->join(
            'product',
            MaterialTrans::class,
            'trans',
            'trans.event = product.event AND trans.local = :local'
        );


        $qb->addSelect('product.id AS value');
        $qb->addSelect('trans.name AS attr');
        $qb->addSelect('info.article AS option');


        /* Кешируем результат ORM */
        return $qb
            ->enableCache('materials-catalog', 86400)
            ->fetchAllAssociativeIndexed(MaterialUid::class);

    }


    /**
     * Метод возвращает активные идентификаторы событий (ProductEventUid) продукции
     */
    public function fetchAllProductEvent(): ?array
    {
        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->from(Material::class, 'product');

        $qb->join(
            ProductInfo::class,
            'info',
            'WITH',
            'info.product = product.id'
        );

        $qb->join(
            ProductActive::class,
            'active',
            'WITH',
            '
            active.event = product.event AND
            active.active = true AND
            active.activeFrom < CURRENT_TIMESTAMP() AND
            (active.activeTo IS NULL OR active.activeTo > CURRENT_TIMESTAMP())
		'
        );

        $qb->join(
            MaterialTrans::class,
            'trans',
            'WITH',
            'trans.event = product.event AND trans.local = :local'
        );


        /* Кешируем результат ORM */
        return $qb->enableCache('materials-catalog', 86400)->getResult();

    }


    /**
     * Метод возвращает идентификаторы событий (ProductEventUid) доступной для продажи продукции
     */
    public function fetchAllProductEventByExists(CategoryProductUid|false $category = false): Generator
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        //$select = sprintf('new %s(product.event, trans.name)', ProductEventUid::class);

        //$qb->select($select);

        $dbal->from(Material::class, 'product');

        if($category)
        {
            $dbal->join(
                'product',
                ProductCategory::class,
                'category',
                'category.event = product.event AND category.category = :category AND category.root = TRUE'
            )
                ->setParameter('category', $category, CategoryProductUid::TYPE);
        }


        $dbal->leftJoin(
            'product',
            MaterialTrans::class,
            'trans',
            'trans.event = product.event AND trans.local = :local'
        );

        $dbal->leftJoin(
            'product',
            ProductPrice::class,
            'product_price',
            'product_price.event = product.event'
        );

        $dbal->leftJoin(
            'product',
            ProductOffer::class,
            'product_offer',
            'product_offer.event = product.event'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariation::class,
            'product_variation',
            'product_variation.offer = product_offer.id'
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

        $dbal
            ->leftJoin(
                'product_offer',
                ProductOfferQuantity::class,
                'product_offer_quantity',
                'product_offer_quantity.offer = product_offer.id'
            );

        $dbal
            ->leftJoin(
                'product_variation',
                ProductVariationQuantity::class,
                'product_variation_quantity',
                'product_variation_quantity.variation = product_variation.id'
            );

        $dbal
            ->leftJoin(
                'product_modification',
                ProductModificationQuantity::class,
                'product_modification_quantity',
                'product_modification_quantity.modification = product_modification.id'
            );


        $dbal->addSelect('product.event AS value');
        $dbal->addSelect('trans.name AS attr');


        $dbal->addSelect('

            CASE
               WHEN SUM(product_modification_quantity.quantity - product_modification_quantity.reserve) > 0
               THEN SUM(product_modification_quantity.quantity - product_modification_quantity.reserve)

               WHEN SUM(product_variation_quantity.quantity - product_variation_quantity.reserve) > 0
               THEN SUM(product_variation_quantity.quantity - product_variation_quantity.reserve)

               WHEN SUM(product_offer_quantity.quantity - product_offer_quantity.reserve) > 0
               THEN SUM(product_offer_quantity.quantity - product_offer_quantity.reserve)

               WHEN SUM(product_price.quantity - product_price.reserve) > 0
               THEN SUM(product_price.quantity - product_price.reserve)

               ELSE 0
            END

        AS option');


        $dbal->andWhere('
            product_modification_quantity.quantity > 0 OR 
            product_variation_quantity.quantity > 0 OR 
            product_offer_quantity.quantity > 0 OR
            product_price.quantity > 0 
        ');


        $dbal->allGroupByExclude();

        return $dbal->enableCache('materials-catalog', 60)->fetchAllHydrate(MaterialEventUid::class);

    }

}
