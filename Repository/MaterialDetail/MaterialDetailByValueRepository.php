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

namespace BaksDev\Materials\Catalog\Repository\MaterialDetail;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Active\MaterialActive;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Description\MaterialDescription;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\MaterialInvariable;
use BaksDev\Materials\Catalog\Entity\Offers\Image\MaterialOfferImage;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Price\MaterialOfferPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Image\MaterialVariationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Image\MaterialModificationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Price\MaterialModificationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\MaterialVariationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Photo\MaterialPhoto;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Entity\Property\MaterialProperty;
use BaksDev\Materials\Catalog\Entity\Seo\MaterialSeo;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Cover\CategoryMaterialCover;
use BaksDev\Materials\Category\Entity\Info\CategoryMaterialInfo;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Trans\CategoryMaterialOffersTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\Trans\CategoryMaterialModificationTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\Trans\CategoryMaterialVariationTrans;
use BaksDev\Materials\Category\Entity\Section\CategoryMaterialSection;
use BaksDev\Materials\Category\Entity\Section\Field\CategoryMaterialSectionField;
use BaksDev\Materials\Category\Entity\Section\Field\Trans\CategoryMaterialSectionFieldTrans;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use BaksDev\Products\Product\Type\Material\MaterialUid;

final readonly class MaterialDetailByValueRepository implements MaterialDetailByValueInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает детальную информацию о сырье и его заполненному значению ТП, вариантов и модификаций.
     *
     * @param ?string $offer - значение торгового предложения
     * @param ?string $variation - значение множественного варианта ТП
     * @param ?string $modification - значение модификации множественного варианта ТП
     */
    public function fetchMaterialAssociative(
        MaterialUid $material,
        ?string $offer = null,
        ?string $variation = null,
        ?string $modification = null,
    ): array|bool
    {


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material');


        $dbal
            ->addSelect('material_active.active')
            ->addSelect('material_active.active_from')
            ->addSelect('material_active.active_to')
            ->join(
                'material',
                MaterialActive::class,
                'material_active',
                'material_active.event = material.event'
            );

        $dbal
            ->addSelect('material_seo.title AS seo_title')
            ->addSelect('material_seo.keywords AS seo_keywords')
            ->addSelect('material_seo.description AS seo_description')
            ->leftJoin(
                'material',
                MaterialSeo::class,
                'material_seo',
                'material_seo.event = material.event AND material_seo.local = :local'
            );


        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );


        $dbal
            ->addSelect('material_desc.preview AS material_preview')
            ->addSelect('material_desc.description AS material_description')
            ->leftJoin(
                'material',
                MaterialDescription::class,
                'material_desc',
                'material_desc.event = material.event AND material_desc.device = :device '
            )->setParameter('device', 'pc');


        /* Базовая Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );

        /* MaterialInfo */

        $dbal
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id '
            );

        /* Торговое предложение */

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.const as material_offer_const')
            ->addSelect('material_offer.value as material_offer_value')
            ->leftJoin(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event '.
                ($offer ? ' AND material_offer.value = :material_offer_value' : '')
            );


        if($offer)
        {
            $dbal->setParameter('material_offer_value', $offer);
        }

        /* Цена торгового предо жения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferPrice::class,
            'material_offer_price',
            'material_offer_price.offer = material_offer.id'
        );

        /* Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        /* Получаем название торгового предложения */
        $dbal
            ->addSelect('category_offer_trans.name as material_offer_name')
            ->leftJoin(
                'category_offer',
                CategoryMaterialOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );

        /* Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        //MaterialCategoryOffers

        /**
         * Множественные варианты торгового предложения
         */

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.const as material_variation_const')
            ->addSelect('material_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id'.
                ($variation ? ' AND material_variation.value = :material_variation_value' : '')
            );

        if($variation)
        {
            $dbal->setParameter('material_variation_value', $variation);
        }

        /* Цена множественного варианта */
        $dbal->leftJoin(
            'material_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        );

        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_variation_trans.name as material_variation_name')
            ->leftJoin(
                'category_variation',
                CategoryMaterialVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );

        /* Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );

        /**
         * Модификация множественного варианта торгового предложения
         */

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.const as material_modification_const')
            ->addSelect('material_modification.value as material_modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id'.
                ($modification ? ' AND material_modification.value = :material_modification_value' : '')
            );

        if($modification)
        {
            $dbal->setParameter('material_modification_value', $modification);
        }

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        );

        /* Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
            );

        /* Получаем название типа модификации */
        $dbal
            ->addSelect('category_modification_trans.name as material_modification_name')
            ->leftJoin(
                'category_modification',
                CategoryMaterialModificationTrans::class,
                'category_modification_trans',
                'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
            );

        /* Наличие и резерв модификации множественного варианта */
        $dbal->leftJoin(
            'category_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_modification.id'
        );

        //$dbal->addSelect("'".Entity\Offers\Variation\Image\MaterialOfferVariationImage::class."' AS upload_image_dir ");

        /* Артикул сырья */

        $dbal->addSelect('
            COALESCE(
                material_modification.article, 
                material_variation.article, 
                material_offer.article, 
                material_info.article
            ) AS material_article
		');

        /* Фото модификаций */

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            '
			material_modification_image.modification = material_modification.id
			'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN material_modification_image.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_modification_image.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialModificationImage::class)."' , '/', material_modification_image.name),
						'material_img_ext', material_modification_image.ext,
						'material_img_cdn', material_modification_image.cdn
						

					) END
			) AS material_modification_image
	"
        );

        /* Фото вариантов */

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            '
			material_variation_image.variation = material_variation.id
			'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN material_variation_image.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_variation_image.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialVariationImage::class)."' , '/', material_variation_image.name),
						'material_img_ext', material_variation_image.ext,
						'material_img_cdn', material_variation_image.cdn
						

					) END
			) AS material_variation_image
	"
        );

        /* Фот оторговых предложений */

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            '
			
			material_offer_images.offer = material_offer.id
			
		'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN material_offer_images.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_offer_images.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialOfferImage::class)."' , '/', material_offer_images.name),
						'material_img_ext', material_offer_images.ext,
						'material_img_cdn', material_offer_images.cdn
						

					) END

				 /*ORDER BY material_photo.root DESC, material_photo.id*/
			) AS material_offer_images
	"
        );

        /* Фот осырья */

        $dbal->leftJoin(
            'material',
            MaterialPhoto::class,
            'material_photo',
            '
	
			material_photo.event = material.event
			'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT

					CASE WHEN material_photo.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_photo.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialPhoto::class)."' , '/', material_photo.name),
						'material_img_ext', material_photo.ext,
						'material_img_cdn', material_photo.cdn
						

					) END

				 /*ORDER BY material_photo.root DESC, material_photo.id*/
			) AS material_photo
	"
        );

        /* Стоимость сырья */

        $dbal->addSelect('
			COALESCE(
                NULLIF(material_modification_price.price, 0), 
                NULLIF(material_variation_price.price, 0), 
                NULLIF(material_offer_price.price, 0), 
                NULLIF(material_price.price, 0),
                0
            ) AS material_price
		');


        /* Валюта сырья */

        $dbal->addSelect(
            '
			CASE
			   WHEN material_modification_price.price IS NOT NULL AND material_modification_price.price > 0 
			   THEN material_modification_price.currency
			   
			   WHEN material_variation_price.price IS NOT NULL AND material_variation_price.price > 0 
			   THEN material_variation_price.currency
			   
			   WHEN material_offer_price.price IS NOT NULL AND material_offer_price.price > 0 
			   THEN material_offer_price.currency
			   
			   WHEN material_price.price IS NOT NULL AND material_price.price > 0 
			   THEN material_price.currency
			   
			   ELSE NULL
			END AS material_currency
		'
        );

        /* Наличие сырья */

        $dbal->addSelect(
            '
			CASE
		
			    WHEN material_modification_quantity.quantity > 0 AND material_modification_quantity.quantity > material_modification_quantity.reserve 
			   THEN (material_modification_quantity.quantity - material_modification_quantity.reserve)

			   WHEN material_variation_quantity.quantity > 0 AND material_variation_quantity.quantity > material_variation_quantity.reserve  
			   THEN (material_variation_quantity.quantity - material_variation_quantity.reserve)
			
			   WHEN material_offer_quantity.quantity > 0 AND material_offer_quantity.quantity > material_offer_quantity.reserve 
			   THEN (material_offer_quantity.quantity - material_offer_quantity.reserve)

			   WHEN material_price.quantity > 0 AND material_price.quantity > material_price.reserve 
			   THEN (material_price.quantity - material_price.reserve)
			   
			   ELSE 0
			END AS material_quantity
		'
        )
            ->addGroupBy('material_modification_quantity.reserve')
            ->addGroupBy('material_variation_quantity.reserve')
            ->addGroupBy('material_offer_quantity.reserve')
            ->addGroupBy('material_price.reserve');


        /* Наличие */
        //		$dbal->addSelect("
        //			CASE
        //			   WHEN material_modification_price.price IS NOT NULL THEN material_modification_price.price
        //			   WHEN material_variation_price.price IS NOT NULL THEN material_variation_price.price
        //			   WHEN material_offer_price.price IS NOT NULL THEN material_offer_price.price
        //			   WHEN material_price.price IS NOT NULL THEN material_price.price
        //			   ELSE NULL
        //			END AS material_price
        //		"
        //		);

        /** Категория */
        $dbal->join(
            'material',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material.event AND material_event_category.root = true'
        );

        $dbal->join(
            'material_event_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_event_category.category'
        );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category',
                CategoryMaterialInfo::class,
                'category_info',
                'category_info.event = category.event'
            );

        $dbal->leftJoin(
            'category',
            CategoryMaterialSection::class,
            'category_section',
            'category_section.event = category.event'
        );

        /** Свойства, участвующие в карточке */
        $dbal->leftJoin(
            'category_section',
            CategoryMaterialSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND (category_section_field.public = TRUE OR category_section_field.name = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryMaterialSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );

        /** Обложка категории */
        $dbal->addSelect('category_cover.ext AS category_cover_ext');
        $dbal->addSelect('category_cover.cdn AS category_cover_cdn');
        $dbal->leftJoin(
            'category',
            CategoryMaterialCover::class,
            'category_cover',
            'category_cover.event = category.event',
        );

        $dbal->addSelect(
            "
			CASE
			   WHEN category_cover.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(CategoryMaterialCover::class)."' , '/', category_cover.name)
			   ELSE NULL
			END AS category_cover_path
		"
        );

        $dbal->leftJoin(
            'category_section_field',
            MaterialProperty::class,
            'material_property',
            'material_property.event = material.event AND material_property.field = category_section_field.const'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
				
					'0', category_section_field.sort, /* сортирвока */
				
					'field_uid', category_section_field.id,
					'field_const', category_section_field.const,
					'field_name', category_section_field.name,
					'field_alternative', category_section_field.alternative,
					'field_public', category_section_field.public,
					'field_card', category_section_field.card,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', material_property.value
				)
			
		)
			AS category_section_field"
        );

        /** Material Invariable */
        $dbal
            ->addSelect('material_invariable.id AS material_invariable_id')
            ->leftJoin(
                'material_modification',
                MaterialInvariable::class,
                'material_invariable',
                '
                    material_invariable.material = material.id AND 
                    
                    (
                        (material_offer.const IS NOT NULL AND material_invariable.offer = material_offer.const) OR 
                        (material_offer.const IS NULL AND material_invariable.offer IS NULL)
                    )
                    
                    AND
                     
                    (
                        (material_variation.const IS NOT NULL AND material_invariable.variation = material_variation.const) OR 
                        (material_variation.const IS NULL AND material_invariable.variation IS NULL)
                    )
                     
                   AND
                   
                   (
                        (material_modification.const IS NOT NULL AND material_invariable.modification = material_modification.const) OR 
                        (material_modification.const IS NULL AND material_invariable.modification IS NULL)
                   )
         
            ');

        $dbal->where('material.id = :material');
        $dbal->setParameter('material', $material, MaterialUid::TYPE);

        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAssociative();
    }


    /**
     * Метод возвращает детальную информацию о сырье и его заполненному значению ТП, вариантов и модификаций.
     *
     * @param ?string $offer - значение торгового предложения
     * @param ?string $variation - значение множественного варианта ТП
     * @param ?string $modification - значение модификации множественного варианта ТП
     */
    public function fetchMaterialEventAssociative(
        MaterialEventUid $event,
        ?string $offer = null,
        ?string $variation = null,
        ?string $modification = null,
    ): array|bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('material_event.main')
            ->addSelect('material_event.id')
            ->from(MaterialEvent::class, 'material_event')
            ->where('material_event.id = :material')
            ->setParameter('material', $event, MaterialEventUid::TYPE);

        $dbal
            ->addSelect('material_active.active')
            ->addSelect('material_active.active_from')
            ->addSelect('material_active.active_to')
            ->join(
                'material',
                MaterialActive::class,
                'material_active',
                'material_active.event = material.event'
            );

        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );


        $dbal
            ->addSelect('material_desc.preview AS material_preview')
            ->addSelect('material_desc.description AS material_description')
            ->leftJoin(
                'material_event',
                MaterialDescription::class,
                'material_desc',
                'material_desc.event = material_event.id AND material_desc.device = :device '
            )->setParameter('device', 'pc');


        /* Базовая Цена товара */
        $dbal->leftJoin(
            'material_event',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material_event.id'
        );

        /* MaterialInfo */

        $dbal
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id '
            );

        /* Торговое предложение */

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.value as material_offer_value')
            ->leftJoin(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event '.($offer ? ' AND material_offer.value = :material_offer_value' : '').' '
            );

        if($offer)
        {
            $dbal->setParameter('material_offer_value', $offer);
        }

        /* Цена торгового предо жения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferPrice::class,
            'material_offer_price',
            'material_offer_price.offer = material_offer.id'
        );

        /* Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        /* Получаем название торгового предложения */
        $dbal
            ->addSelect('category_offer_trans.name as material_offer_name')
            ->leftJoin(
                'category_offer',
                CategoryMaterialOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );

        /* Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        //MaterialCategoryOffers

        /* Множественные варианты торгового предложения */

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id'.($variation ? ' AND material_variation.value = :material_variation_value' : '').' '
            );

        if($variation)
        {
            $dbal->setParameter('material_variation_value', $variation);
        }

        /* Цена множественного варианта */
        $dbal->leftJoin(
            'material_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        );

        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_variation_trans.name as material_variation_name')
            ->leftJoin(
                'category_variation',
                CategoryMaterialVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );

        /* Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );

        /* Модификация множественного варианта торгового предложения */

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.value as material_modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id'.($modification ? ' AND material_modification.value = :material_modification_value' : '').' '
            );

        if($modification)
        {
            $dbal->setParameter('material_modification_value', $modification);
        }

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        );

        /* Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
            );

        /* Получаем название типа модификации */
        $dbal
            ->addSelect('category_modification_trans.name as material_modification_name')
            ->leftJoin(
                'category_modification',
                CategoryMaterialModificationTrans::class,
                'category_modification_trans',
                'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
            );

        /* Наличие и резерв модификации множественного варианта */
        $dbal->leftJoin(
            'category_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_modification.id'
        );

        //$dbal->addSelect("'".Entity\Offers\Variation\Image\MaterialOfferVariationImage::class."' AS upload_image_dir ");

        /* Артикул сырья */

        $dbal->addSelect('
            COALESCE(
                material_modification.article, 
                material_variation.article, 
                material_offer.article, 
                material_info.article
            ) AS material_article
		');

        /* Фото модификаций */

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            '
			material_modification_image.modification = material_modification.id
			'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN material_modification_image.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_modification_image.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialModificationImage::class)."' , '/', material_modification_image.name),
						'material_img_ext', material_modification_image.ext,
						'material_img_cdn', material_modification_image.cdn
						

					) END
			) AS material_modification_image
	"
        );

        /* Фото вариантов */

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            '
			material_variation_image.variation = material_variation.id
			'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN material_variation_image.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_variation_image.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialVariationImage::class)."' , '/', material_variation_image.name),
						'material_img_ext', material_variation_image.ext,
						'material_img_cdn', material_variation_image.cdn
						

					) END
			) AS material_variation_image
	"
        );

        /* Фот оторговых предложений */

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            '
			
			material_offer_images.offer = material_offer.id
			
		'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
				CASE WHEN material_offer_images.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_offer_images.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialOfferImage::class)."' , '/', material_offer_images.name),
						'material_img_ext', material_offer_images.ext,
						'material_img_cdn', material_offer_images.cdn
						

					) END

				 /*ORDER BY material_photo.root DESC, material_photo.id*/
			) AS material_offer_images
	"
        );

        /* Фот осырья */

        $dbal->leftJoin(
            'material_offer',
            MaterialPhoto::class,
            'material_photo',
            '
	
			material_photo.event = material_event.id
			'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT

					CASE WHEN material_photo.ext IS NOT NULL THEN
					JSONB_BUILD_OBJECT
					(
						'material_img_root', material_photo.root,
						'material_img', CONCAT ( '/upload/".$dbal->table(MaterialPhoto::class)."' , '/', material_photo.name),
						'material_img_ext', material_photo.ext,
						'material_img_cdn', material_photo.cdn
						

					) END

				 /*ORDER BY material_photo.root DESC, material_photo.id*/
			) AS material_photo
	"
        );

        /* Стоимость сырья */

        $dbal->addSelect('
			COALESCE(
                NULLIF(material_modification_price.price, 0), 
                NULLIF(material_variation_price.price, 0), 
                NULLIF(material_offer_price.price, 0), 
                NULLIF(material_price.price, 0),
                0
            ) AS material_price
		');

        /* Валюта сырья */

        $dbal->addSelect(
            '
			CASE
			   WHEN material_modification_price.price IS NOT NULL AND material_modification_price.price > 0 
			   THEN material_modification_price.currency
			   
			   WHEN material_variation_price.price IS NOT NULL AND material_variation_price.price > 0 
			   THEN material_variation_price.currency
			   
			   WHEN material_offer_price.price IS NOT NULL AND material_offer_price.price > 0 
			   THEN material_offer_price.currency
			   
			   WHEN material_price.price IS NOT NULL AND material_price.price > 0 
			   THEN material_price.currency
			   
			   ELSE NULL
			END AS material_currency
		'
        );

        /* Наличие сырья */

        $dbal->addSelect(
            '

            CASE
			   WHEN material_modification_quantity.quantity > 0 AND material_modification_quantity.quantity > material_modification_quantity.reserve 
			   THEN (material_modification_quantity.quantity - material_modification_quantity.reserve)
			
			   WHEN material_variation_quantity.quantity > 0 AND material_variation_quantity.quantity > material_variation_quantity.reserve 
			   THEN (material_variation_quantity.quantity - material_variation_quantity.reserve)
			
			   WHEN material_offer_quantity.quantity > 0 AND material_offer_quantity.quantity > material_offer_quantity.reserve 
			   THEN (material_offer_quantity.quantity - material_offer_quantity.reserve)
			  
			   WHEN material_price.quantity > 0 AND material_price.quantity > material_price.reserve 
			   THEN (material_price.quantity - material_price.reserve)
			 
			   ELSE 0
			   
			END AS material_quantity
            
		'
        )
            ->addGroupBy('material_modification_quantity.reserve')
            ->addGroupBy('material_variation_quantity.reserve')
            ->addGroupBy('material_offer_quantity.reserve')
            ->addGroupBy('material_price.reserve');


        /* Категория */
        $dbal->join(
            'material_event',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material_event.id AND material_event_category.root = true'
        );


        $dbal->join(
            'material_event_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_event_category.category'
        );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $dbal->addSelect('category_info.url AS category_url');
        $dbal->leftJoin(
            'category',
            CategoryMaterialInfo::class,
            'category_info',
            'category_info.event = category.event'
        );

        $dbal->leftJoin(
            'category',
            CategoryMaterialSection::class,
            'category_section',
            'category_section.event = category.event'
        );

        /* Свойства, учавствующие в карточке */

        $dbal->leftJoin(
            'category_section',
            CategoryMaterialSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND (category_section_field.public = TRUE OR category_section_field.name = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryMaterialSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );

        $dbal->leftJoin(
            'category_section_field',
            MaterialProperty::class,
            'material_property',
            'material_property.event = material.event AND material_property.field = category_section_field.const'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
				
					'0', category_section_field.sort, /* сортирвока */
				
					'field_uid', category_section_field.id,
					'field_const', category_section_field.const,
					'field_name', category_section_field.name,
					'field_alternative', category_section_field.alternative,
					'field_public', category_section_field.public,
					'field_card', category_section_field.card,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', material_property.value
				)
			
		)
			AS category_section_field"
        );


        /* Кешируем результат DBAL */
        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAssociative();

    }

}
