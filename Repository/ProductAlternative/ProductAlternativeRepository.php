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

namespace BaksDev\Materials\Catalog\Repository\ProductAlternative;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Active\ProductActive;
use BaksDev\Materials\Catalog\Entity\Category\ProductCategory;
use BaksDev\Materials\Catalog\Entity\Info\ProductInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Price\ProductPrice;
use BaksDev\Materials\Catalog\Entity\Property\ProductProperty;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;
use BaksDev\Products\Category\Entity\Section\CategoryProductSection;
use BaksDev\Products\Category\Entity\Section\Field\CategoryProductSectionField;
use BaksDev\Products\Category\Entity\Section\Field\Trans\CategoryProductSectionFieldTrans;
use BaksDev\Products\Category\Entity\Trans\CategoryProductTrans;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use stdClass;

final class ProductAlternativeRepository implements ProductAlternativeInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function fetchAllAlternativeAssociative(
        string $offer,
        ?string $variation,
        ?string $modification,
        ?array $property = null
    ): ?array
    {

        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        // ТОРГОВОЕ ПРЕДЛОЖЕНИЕ

        $qb
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->addSelect('product_offer.id as product_offer_uid')
            ->from(ProductOffer::class, 'product_offer');


        $qb
            ->addSelect('product.id')
            ->addSelect('product.event')
            ->join(
                'product_offer',
                Material::class,
                'product',
                'product.event = product_offer.event'
            );


        // МНОЖЕСТВЕННЫЕ ВАРИАНТЫ

        $variationMethod = empty($variation) ? 'leftJoin' : 'join';

        $qb
            ->addSelect('product_variation.value as product_variation_value')
            ->addSelect('product_variation.postfix as product_variation_postfix')
            ->addSelect('product_variation.id as product_variation_uid')
            ->{$variationMethod}(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id '.(empty($variation) ? '' : 'AND product_variation.value = :variation')
            );

        if(!empty($variation))
        {
            $qb->setParameter('variation', $variation);
        }

        $modificationMethod = empty($modification) ? 'leftJoin' : 'join';

        // МОДИФИКАЦИЯ
        $qb
            ->addSelect('product_modification.value as product_modification_value')
            ->addSelect('product_modification.postfix as product_modification_postfix')
            ->addSelect('product_modification.id as product_modification_uid')
            ->{$modificationMethod}(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.variation = product_variation.id '.(empty($modification) ? '' : 'AND product_modification.value = :modification')
            )
            ->addGroupBy('product_modification.article');

        if(!empty($modification))
        {
            $qb->setParameter('modification', $modification);
        }


        // Проверяем активность продукции
        $qb
            ->addSelect('product_active.active_from')
            ->join(
                'product',
                ProductActive::class,
                'product_active',
                'product_active.event = product.event AND product_active.active = true AND product_active.active_from < NOW()
			
			AND (
				CASE
				   WHEN product_active.active_to IS NOT NULL 
				   THEN product_active.active_to > NOW()
				   ELSE TRUE
				END
			)
		'
            );


        // Название твоара
        $qb
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product',
                MaterialTrans::class,
                'product_trans',
                'product_trans.event = product.event AND product_trans.local = :local'
            );


        $qb
            ->addSelect('product_info.url AS product_url')
            ->leftJoin(
                'product',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id '
            );

        // Артикул продукта

        $qb->addSelect(
            '
			CASE
			   WHEN product_modification.article IS NOT NULL 
			   THEN product_modification.article
			   
			   WHEN product_variation.article IS NOT NULL 
			   THEN product_variation.article
			   
			   WHEN product_offer.article IS NOT NULL THEN 
			   product_offer.article
			   
			   WHEN product_info.article IS NOT NULL 
			   THEN product_info.article
			   
			   ELSE NULL
			END AS article
		'
        );


        /**
         * ТИПЫ ТОРГОВЫХ ПРЕДЛОЖЕНИЙ
         */


        // Получаем тип торгового предложения
        $qb
            ->addSelect('category_offer.reference AS product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        // Получаем название торгового предложения
        $qb
            ->addSelect('category_offer_trans.name as product_offer_name')
            ->leftJoin(
                'category_offer',
                CategoryProductOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );


        // Получаем тип множественного варианта
        $qb
            ->addSelect('category_offer_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = product_variation.category_variation'
            );

        // Получаем название множественного варианта
        $qb
            ->addSelect('category_offer_variation_trans.name as product_variation_name')
            ->leftJoin(
                'category_offer_variation',
                CategoryProductVariationTrans::class,
                'category_offer_variation_trans',
                'category_offer_variation_trans.variation = category_offer_variation.id AND category_offer_variation_trans.local = :local'
            );

        // Получаем тип модификации множественного варианта
        $qb
            ->addSelect('category_offer_modification.reference as product_modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_offer_modification',
                'category_offer_modification.id = product_modification.category_modification'
            );

        // Получаем название типа модификации
        $qb
            ->addSelect('category_offer_modification_trans.name as product_modification_name')
            ->leftJoin(
                'category_offer_modification',
                CategoryProductModificationTrans::class,
                'category_offer_modification_trans',
                'category_offer_modification_trans.modification = category_offer_modification.id AND category_offer_modification_trans.local = :local'
            );


        /**
         * СТОИМОСТЬ И ВАЛЮТА ПРОДУКТА
         */

        $qb->addSelect(
            '
			CASE
			   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 
			   THEN product_modification_price.price
			   
			   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 
			   THEN product_variation_price.price
			   
			   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 
			   THEN product_offer_price.price
			   
			   WHEN product_price.price IS NOT NULL AND product_price.price > 0 
			   THEN product_price.price
			   
			   ELSE NULL
			END AS price
		'
        );


        // Валюта продукта

        $qb->addSelect(
            '
			CASE
			   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 
			   THEN product_modification_price.currency
			   
			   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 
			   THEN product_variation_price.currency
			   
			   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 
			   THEN product_offer_price.currency
			   
			   WHEN product_price.price IS NOT NULL AND product_price.price > 0 
			   THEN product_price.currency
			   
			   ELSE NULL
			END AS currency
		'
        );

        // Базовая Цена товара
        $qb
            ->leftJoin(
                'product',
                ProductPrice::class,
                'product_price',
                'product_price.event = product.event'
            )
            ->addGroupBy('product_price.currency')
            ->addGroupBy('product_price.reserve');

        $qb
            ->leftJoin(
                'product_offer',
                ProductOfferPrice::class,
                'product_offer_price',
                'product_offer_price.offer = product_offer.id'
            )
            ->addGroupBy('product_offer_price.currency');

        // Цена множественного варианта
        $qb
            ->leftJoin(
                'product_variation',
                ProductVariationPrice::class,
                'product_variation_price',
                'product_variation_price.variation = product_variation.id'
            )
            ->addGroupBy('product_variation_price.currency');


        // Цена модификации множественного варианта
        $qb
            ->leftJoin(
                'product_modification',
                ProductModificationPrice::class,
                'product_modification_price',
                'product_modification_price.modification = product_modification.id'
            )
            ->addGroupBy('product_modification_price.currency');


        /**
         * НАЛИЧИЕ ПРОДУКТА
         */

        $qb->addSelect(
            '

			CASE
			
			   WHEN product_modification_quantity.quantity > 0 AND product_modification_quantity.quantity > product_modification_quantity.reserve 
			   THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)

			   WHEN product_variation_quantity.quantity > 0 AND product_variation_quantity.quantity > product_variation_quantity.reserve  
			   THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
			
			   WHEN product_offer_quantity.quantity > 0 AND product_offer_quantity.quantity > product_offer_quantity.reserve 
			   THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)

			   WHEN product_price.quantity > 0 AND product_price.quantity > product_price.reserve 
			   THEN (product_price.quantity - product_price.reserve)
			
			   ELSE 0
			   
			END AS quantity
		'
        );

        // Наличие и резерв торгового предложения

        $qb
            ->leftJoin(
                'product_offer',
                ProductOfferQuantity::class,
                'product_offer_quantity',
                'product_offer_quantity.offer = product_offer.id'
            )
            ->addGroupBy('product_offer_quantity.reserve');

        // Наличие и резерв множественного варианта
        $qb
            ->leftJoin(
                'category_offer_variation',
                ProductVariationQuantity::class,
                'product_variation_quantity',
                'product_variation_quantity.variation = product_variation.id'
            )
            ->addGroupBy('product_variation_quantity.reserve');

        // Наличие и резерв модификации множественного варианта
        $qb
            ->leftJoin(
                'category_offer_modification',
                ProductModificationQuantity::class,
                'product_modification_quantity',
                'product_modification_quantity.modification = product_modification.id'
            )
            ->addGroupBy('product_modification_quantity.reserve');


        /**
         * КАТЕГОРИЯ
         */

        $qb->leftJoin(
            'product',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product.event AND product_event_category.root = true'
        );


        $qb->leftJoin(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );

        $qb
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $qb
            ->addSelect('category_info.url AS category_url')
            ->join(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event AND category_info.active = true'
            );

        $qb->leftJoin(
            'category',
            CategoryProductSection::class,
            'category_section',
            'category_section.event = category.event'
        );


        /**
         * СВОЙСТВА, УЧАВСТВУЮЩИЕ В ФИЛЬТРЕ АЛЬТЕРНАТИВ
         */

        if($property)
        {
            /** @var stdClass $props */
            foreach($property as $props)
            {
                if(empty($props->field_uid))
                {
                    continue;
                }

                $alias = md5($props->field_uid);

                $qb->join(
                    'product_offer',
                    ProductProperty::class,
                    'product_property_'.$alias,
                    'product_property_'.$alias.'.event = product_offer.event AND product_property_'.$alias.'.value = :props_'.$alias
                );

                $qb->setParameter('props_'.$alias, $props->field_value);
            }
        }

        /**
         * СВОЙСТВА, УЧАСТВУЮЩИЕ В ПРЕВЬЮ
         */

        $qb->leftJoin(
            'category_section',
            CategoryProductSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND category_section_field.card = TRUE'
        );

        $qb->leftJoin(
            'category_section_field',
            CategoryProductSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );

        $qb->leftJoin(
            'category_section_field',
            ProductProperty::class,
            'category_product_property',
            'category_product_property.event = product.event AND category_product_property.field = category_section_field.const'
        );

        $qb->addSelect(
            "JSON_AGG
		( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
				
					'0', category_section_field.sort,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', category_product_property.value
				)
			
		)
			AS category_section_field"
        );

        $qb->where('product_offer.value = :offer');
        $qb->setParameter('offer', $offer);
        $qb->setMaxResults(1000);

        $qb->allGroupByExclude();

        $qb->orderBy('quantity', 'DESC');

        return $qb
            ->enableCache('materials-catalog', 86400)
            ->fetchAllAssociative();
    }
}
