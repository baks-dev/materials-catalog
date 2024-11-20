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

namespace BaksDev\Materials\Catalog\Repository\ProductModel;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Active\ProductActive;
use BaksDev\Materials\Catalog\Entity\Category\ProductCategory;
use BaksDev\Materials\Catalog\Entity\Description\ProductDescription;
use BaksDev\Materials\Catalog\Entity\Info\ProductInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Materials\Catalog\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Photo\ProductPhoto;
use BaksDev\Materials\Catalog\Entity\Price\ProductPrice;
use BaksDev\Materials\Catalog\Entity\Property\ProductProperty;
use BaksDev\Materials\Catalog\Entity\Seo\ProductSeo;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Cover\CategoryProductCover;
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

final class ProductModelRepository implements ProductModelInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function fetchModelAssociative(MaterialUid $product): array|bool
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->select('product.id')
            ->addSelect('product.event')
            ->from(Material::class, 'product');


        $dbal
            ->addSelect('product_active.active')
            ->addSelect('product_active.active_from')
            ->addSelect('product_active.active_to')
            ->join(
                'product',
                ProductActive::class,
                'product_active',
                '
			product_active.event = product.event
			'
            );

        $dbal
            ->addSelect('product_seo.title AS seo_title')
            ->addSelect('product_seo.keywords AS seo_keywords')
            ->addSelect('product_seo.description AS seo_description')
            ->leftJoin(
                'product',
                ProductSeo::class,
                'product_seo',
                'product_seo.event = product.event AND product_seo.local = :local'
            );


        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product',
                MaterialTrans::class,
                'product_trans',
                'product_trans.event = product.event AND product_trans.local = :local'
            );

        $dbal
            ->addSelect('product_desc.preview AS product_preview')
            ->addSelect('product_desc.description AS product_description')
            ->leftJoin(
                'product',
                ProductDescription::class,
                'product_desc',
                'product_desc.event = product.event AND product_desc.device = :device '
            )
            ->setParameter('device', 'pc');


        /** Цена товара */
        $dbal->leftJoin(
            'product',
            ProductPrice::class,
            'product_price',
            'product_price.event = product.event'
        );

        /* ProductInfo */

        $dbal
            ->addSelect('product_info.url')
            ->leftJoin(
                'product',
                ProductInfo::class,
                'product_info',
                'product_info.product = product.id '
            );


        /** Торговое предложение */

        $dbal->leftJoin(
            'product',
            ProductOffer::class,
            'product_offer',
            'product_offer.event = product.event'
        );

        /** Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /** Получаем название торгового предложения */
        $dbal
            ->leftJoin(
                'category_offer',
                CategoryProductOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );


        $dbal
            ->leftOneJoin(
                'product_offer',
                ProductOfferPrice::class,
                'product_offer_price',
                'product_offer_price.offer = product_offer.id',
                'offer'
            );


        /** Наличие и резерв торгового предложения */
        $dbal
            ->leftJoin(
                'product_offer',
                ProductOfferQuantity::class,
                'product_offer_quantity',
                'product_offer_quantity.offer = product_offer.id'
            );


        /** Множественные варианты торгового предложения */


        $dbal
            ->leftJoin(
                'product_offer',
                ProductVariation::class,
                'product_variation',
                'product_variation.offer = product_offer.id'
            );

        $dbal
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = product_variation.category_variation'
            );


        $dbal
            ->leftJoin(
                'category_variation',
                CategoryProductVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );

        $dbal
            ->leftOneJoin(
                'product_variation',
                ProductVariationPrice::class,
                'product_variation_price',
                'product_variation_price.variation = product_variation.id',
                'variation'
            );


        /* Наличие и резерв множественного варианта */
        $dbal
            ->leftJoin(
                'category_variation',
                ProductVariationQuantity::class,
                'product_variation_quantity',
                'product_variation_quantity.variation = product_variation.id'
            );


        /** Модификация множественного варианта торгового предложения */

        $dbal
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.variation = product_variation.id'
            );

        /** Получаем название типа */

        $dbal
            ->leftJoin(
                'category_modification',
                CategoryProductModificationTrans::class,
                'category_modification_trans',
                'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
            );

        $dbal
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = product_modification.category_modification'
            );


        $dbal
            ->leftOneJoin(
                'product_modification',
                ProductModificationPrice::class,
                'product_modification_price',
                'product_modification_price.modification = product_modification.id',
                'modification'
            );


        /* Наличие и резерв модификации множественного варианта */
        $dbal
            ->leftJoin(
                'category_modification',
                ProductModificationQuantity::class,
                'product_modification_quantity',
                'product_modification_quantity.modification = product_modification.id'
            );


        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT

					JSONB_BUILD_OBJECT
					(

						/* свойства для сортирвоки JSON */
						'0', CONCAT(product_offer.value, product_variation.value, product_modification.value, product_modification_price.price),

						'offer_uid', product_offer.id,
						'offer_value', product_offer.value, /* значение торгового предложения */
						'offer_postfix', product_offer.postfix, /* постфикс торгового предложения */
						'offer_reference', category_offer.reference, /* тип (field) торгового предложения */
						'offer_name', category_offer_trans.name, /* Название свойства */

						'variation_uid', product_variation.id,
						'variation_value', product_variation.value, /* значение множественного варианта */
						'variation_postfix', product_variation.postfix, /* постфикс множественного варианта */
						'variation_reference', category_variation.reference, /* тип (field) множественного варианта */
						'variation_name', category_variation_trans.name, /* Название свойства */

						'modification_uid', product_modification.id,
						'modification_value', product_modification.value, /* значение модификации */
						'modification_postfix', product_modification.postfix, /* постфикс модификации */
						'modification_reference', category_modification.reference, /* тип (field) модификации */
						'modification_name', category_modification_trans.name, /* артикул модификации */
						
						'article', CASE
						   WHEN product_modification.article IS NOT NULL THEN product_modification.article
						   WHEN product_variation.article IS NOT NULL THEN product_variation.article
						   WHEN product_offer.article IS NOT NULL THEN product_offer.article
						   WHEN product_info.article IS NOT NULL THEN product_info.article
						   ELSE NULL
						END,
						
					
						
						'price', CASE
						   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 THEN product_modification_price.price
						   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 THEN product_variation_price.price
						   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 THEN product_offer_price.price
						   WHEN product_price.price IS NOT NULL AND product_price.price > 0 THEN product_price.price
						   ELSE NULL
						END,
						
						'currency', CASE
						   WHEN product_modification_price.price IS NOT NULL AND product_modification_price.price > 0 THEN product_modification_price.currency
						   WHEN product_variation_price.price IS NOT NULL AND product_variation_price.price > 0 THEN product_variation_price.currency
						   WHEN product_offer_price.price IS NOT NULL AND product_offer_price.price > 0 THEN product_offer_price.currency
						   WHEN product_price.price IS NOT NULL AND product_price.price > 0 THEN product_price.currency
						   ELSE NULL
						END,
						
						'quantity', CASE
						   WHEN product_modification_quantity.quantity IS NOT NULL THEN (product_modification_quantity.quantity - product_modification_quantity.reserve)
						   WHEN product_variation_quantity.quantity IS NOT NULL THEN (product_variation_quantity.quantity - product_variation_quantity.reserve)
						   WHEN product_offer_quantity.quantity IS NOT NULL THEN (product_offer_quantity.quantity - product_offer_quantity.reserve)
						   WHEN product_price.quantity IS NOT NULL THEN (product_price.quantity - product_price.reserve)
						   ELSE NULL
						END

					)

			)
			AS product_offers"
        );


        /** Фото модификаций */

        $dbal->leftJoin(
            'product_modification',
            ProductModificationImage::class,
            'product_modification_image',
            ' product_modification_image.modification = product_modification.id'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN product_modification_image.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'product_img_root', product_modification_image.root,
						'product_img', CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name),
						'product_img_ext', product_modification_image.ext,
						'product_img_cdn', product_modification_image.cdn
						

					) END
			) AS product_modification_image
	"
        );


        /* Фото вариантов */

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id'
        );

        $dbal
            ->addSelect(
                "JSON_AGG
		( DISTINCT
				CASE WHEN product_variation_image.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'product_img_root', product_variation_image.root,
						'product_img', CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name),
						'product_img_ext', product_variation_image.ext,
						'product_img_cdn', product_variation_image.cdn
						
					) END
			) AS product_variation_image
	"
            );


        /* Фот оторговых предложений */

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN product_offer_images.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'product_img_root', product_offer_images.root,
						'product_img', CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name),
						'product_img_ext', product_offer_images.ext,
						'product_img_cdn', product_offer_images.cdn
						
					) END

				 /*ORDER BY product_photo.root DESC, product_photo.id*/
			) AS product_offer_images
	"
        );

        /** Фот опродукта */

        $dbal
            ->leftJoin(
                'product_offer',
                ProductPhoto::class,
                'product_photo',
                'product_photo.event = product.event'
            );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT

					CASE WHEN product_photo.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'product_img_root', product_photo.root,
						'product_img', CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name),
						'product_img_ext', product_photo.ext,
						'product_img_cdn', product_photo.cdn
						

					) END

				 /*ORDER BY product_photo.root DESC, product_photo.id*/
			) AS product_photo
	"
        );


        /* Категория */
        $dbal->join(
            'product',
            ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product.event AND product_event_category.root = true'
        );


        $dbal->join(
            'product_event_category',
            CategoryProduct::class,
            'category',
            'category.id = product_event_category.category'
        );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryProductTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category',
                CategoryProductInfo::class,
                'category_info',
                'category_info.event = category.event'
            );

        $dbal->leftJoin(
            'category',
            CategoryProductSection::class,
            'category_section',
            'category_section.event = category.event'
        );

        /** Обложка */

        $dbal
            ->addSelect('category_cover.ext AS category_cover_ext')
            ->addSelect('category_cover.cdn AS category_cover_cdn')
            ->addSelect(
                "
			CASE
                 WHEN category_cover.name IS NOT NULL 
                 THEN CONCAT ( '/upload/".$dbal->table(CategoryProductCover::class)."' , '/', category_cover.name)
                 ELSE NULL
			END AS category_cover_dir
		"
            );


        $dbal->leftJoin(
            'category',
            CategoryProductCover::class,
            'category_cover',
            'category_cover.event = category.event'
        );


        /** Свойства, учавствующие в карточке */

        $dbal->leftJoin(
            'category_section',
            CategoryProductSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND (category_section_field.public = TRUE OR category_section_field.name = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryProductSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );

        $dbal->leftJoin(
            'category_section_field',
            ProductProperty::class,
            'product_property',
            'product_property.event = product.event AND product_property.field = category_section_field.const'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
					'0', category_section_field.sort,
					
					'field_name', category_section_field.name,
					'field_public', category_section_field.public,
					'field_card', category_section_field.card,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', product_property.value
				)
			
		)
			AS category_section_field"
        );

        $dbal->where('product.id = :product');
        $dbal->setParameter('product', $product, MaterialUid::TYPE);

        //dd($dbal->analyze());

        $dbal->allGroupByExclude();

        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAssociative();
    }

}
