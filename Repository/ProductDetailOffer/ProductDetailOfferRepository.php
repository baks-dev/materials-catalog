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

namespace BaksDev\Materials\Catalog\Repository\ProductDetailOffer;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Entity\Price\ProductPrice;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;

final class ProductDetailOfferRepository implements ProductDetailOfferInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(DBALQueryBuilder $DBALQueryBuilder)
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /** Метод возвращает торговые предложения продукта */
    public function fetchProductOfferAssociative(
        MaterialUid|string $product,
    ): array|bool
    {
        if(is_string($product))
        {
            $product = new MaterialUid($product);
        }

        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb
            ->select('product.id')
            ->from(Material::class, 'product')
            ->where('product.id = :product')
            ->setParameter('product', $product, MaterialUid::TYPE);


        /* Цена товара */
        $qb->leftJoin(
            'product',
            ProductPrice::class,
            'product_price',
            'product_price.event = product.event'
        );

        /* Торговое предложение */

        $qb
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->addSelect('product_offer.category_offer as category_offer')
            ->leftJoin(
                'product',
                ProductOffer::class,
                'product_offer',
                'product_offer.event = product.event'
            );

        /* Цена торгового предложения */
        $qb->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id'
        );

        /* Получаем тип торгового предложения */
        $qb
            ->addSelect('category_offer.reference AS product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /* Получаем название торгового предложения */
        $qb
            ->addSelect('category_offer_trans.name as product_offer_name')
            ->addSelect('category_offer_trans.postfix as product_offer_name_postfix')
            ->leftJoin(
                'category_offer',
                CategoryProductOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );

        /* Множественные варианты торгового предложения */

        $qb
            ->addSelect('product_offer_variation.value as product_variation_value')
            ->addSelect('product_offer_variation.postfix as product_variation_postfix')
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_offer_variation',
                'product_offer_variation.offer = product_offer.id'
            );

        /* Цена множественного варианта */
        $qb->leftJoin(
            'category_offer_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_offer_variation.id'
        );

        /* Получаем тип множественного варианта */
        $qb
            ->addSelect('category_offer_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_offer_variation',
                CategoryProductVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = product_offer_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $qb
            ->addSelect('category_offer_variation_trans.name as product_variation_name')
            ->addSelect('category_offer_variation_trans.postfix as product_variation_name_postfix')
            ->leftJoin(
                'category_offer_variation',
                CategoryProductVariationTrans::class,
                'category_offer_variation_trans',
                'category_offer_variation_trans.variation = category_offer_variation.id AND category_offer_variation_trans.local = :local'
            );

        /* Модификация множественного варианта торгового предложения */

        $qb
            ->addSelect('product_offer_modification.value as product_modification_value')
            ->addSelect('product_offer_modification.postfix as product_modification_postfix')
            ->leftJoin(
                'product_offer_variation',
                ProductModification::class,
                'product_offer_modification',
                'product_offer_modification.variation = product_offer_variation.id'
            );

        /* Цена Модификации множественного варианта */
        $qb->leftJoin(
            'product_offer_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_offer_modification.id'
        );

        /* Получаем тип множественного варианта */
        $qb
            ->addSelect('category_offer_modification.reference as product_modification_reference')
            ->leftJoin(
                'product_offer_modification',
                CategoryProductModification::class,
                'category_offer_modification',
                'category_offer_modification.id = product_offer_modification.category_modification'
            );

        /* Получаем название типа */
        $qb
            ->addSelect('category_offer_modification_trans.name as product_modification_name')
            ->addSelect('category_offer_modification_trans.postfix as product_modification_name_postfix')
            ->leftJoin(
                'category_offer_modification',
                CategoryProductModificationTrans::class,
                'category_offer_modification_trans',
                'category_offer_modification_trans.modification = category_offer_modification.id AND category_offer_modification_trans.local = :local'
            );


        /* Стоимость продукта */

        $qb->addSelect('product_variation_price.price');


        $qb->addSelect(
            '
			CASE
			   WHEN product_modification_price.price IS NOT NULL 
			   THEN product_modification_price.price
			   
			   WHEN product_variation_price.price IS NOT NULL 
			   THEN product_variation_price.price
			   
			   WHEN product_offer_price.price IS NOT NULL 
			   THEN product_offer_price.price
			   
			   WHEN product_price.price IS NOT NULL 
			   THEN product_price.price
			   
			   ELSE NULL
			END AS product_price
		'
        );

        /* Валюта продукта */
        $qb->addSelect(
            '
			CASE
			   WHEN product_modification_price.price IS NOT NULL 
			   THEN product_modification_price.currency
			   
			   WHEN product_variation_price.price IS NOT NULL 
			   THEN product_variation_price.currency
			   
			   WHEN product_offer_price.price IS NOT NULL 
			   THEN product_offer_price.currency
			   
			   WHEN product_price.price IS NOT NULL 
			   THEN product_price.currency
			   
			   ELSE NULL
			END AS product_currency
		'
        );

        return $qb->enableCache('materials-catalog', 86400)->fetchAllAssociative();
    }
}
