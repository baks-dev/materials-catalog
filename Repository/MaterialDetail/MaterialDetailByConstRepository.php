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
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
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
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Trans\CategoryMaterialOffersTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\Trans\CategoryMaterialModificationTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\Trans\CategoryMaterialVariationTrans;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use InvalidArgumentException;

final class MaterialDetailByConstRepository implements MaterialDetailByConstInterface
{
    private MaterialUid|false $material = false;

    private MaterialOfferConst|false $offer = false;

    private MaterialVariationConst|false $variation = false;

    private MaterialModificationConst|false $modification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function material(Material|MaterialUid|string $material): self
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        if($material instanceof Material)
        {
            $material = $material->getId();
        }

        $this->material = $material;

        return $this;
    }

    public function offerConst(MaterialOffer|MaterialOfferConst|string|null|false $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new MaterialOfferConst($offer);
        }

        if($offer instanceof MaterialOffer)
        {
            $offer = $offer->getConst();
        }

        $this->offer = $offer;

        return $this;
    }

    public function variationConst(MaterialVariation|MaterialVariationConst|string|null|false $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new MaterialVariationConst($variation);
        }

        if($variation instanceof MaterialVariation)
        {
            $variation = $variation->getConst();
        }

        $this->variation = $variation;

        return $this;
    }

    public function modificationConst(MaterialModification|MaterialModificationConst|string|null|false $modification
    ): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new MaterialModificationConst($modification);
        }

        if($modification instanceof MaterialModification)
        {
            $modification = $modification->getConst();
        }

        $this->modification = $modification;

        return $this;
    }


    /**
     * Метод возвращает детальную информацию о сырье по его неизменяемым идентификаторам по иерархии
     * 1. модификаций множественного варианта торгового предложения
     * 2. множественного варианта торгового предложения
     * 3. торгового предложения,
     */
    public function find(): array|false
    {
        if($this->material === false)
        {
            throw new InvalidArgumentException('Invalid Argument material');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material')
            ->where('material.id = :material')
            ->setParameter('material', $this->material, MaterialUid::TYPE);


        //        $dbal
        //            ->addSelect('material_active.active')
        //            ->addSelect('material_active.active_from')
        //            ->addSelect('material_active.active_to')
        //            ->leftJoin(
        //                'material',
        //                MaterialActive::class,
        //                'material_active',
        //                'material_active.event = material.event'
        //            );

        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );

        //        $dbal
        //            ->addSelect('material_desc.preview AS material_preview')
        //            ->addSelect('material_desc.description AS material_description')
        //            ->leftJoin(
        //                'material',
        //                MaterialDescription::class,
        //                'material_desc',
        //                'material_desc.event = material.event AND material_desc.device = :device '
        //            )
        //            ->setParameter('device', 'pc');

        /* Базовая Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );

        /* Базовый артикул сырья и стоимость */
        $dbal
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id '
            );

        /**
         * Торговое предложение
         */
        if(false !== $this->offer)
        {
            $dbal
                ->join(
                    'material',
                    MaterialOffer::class,
                    'material_offer',
                    '
                        material_offer.event = material.event AND 
                        material_offer.const = :material_offer_const'
                )
                ->setParameter(
                    'material_offer_const',
                    $this->offer,
                    MaterialOfferConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'material',
                    MaterialOffer::class,
                    'material_offer',
                    'material_offer.event = material.event'
                );
        }

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.const as material_offer_const')
            ->addSelect('material_offer.value as material_offer_value');


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


        /**
         * Множественные варианты торгового предложения
         */
        if(false !== $this->variation)
        {
            $dbal
                ->join(
                    'material_offer',
                    MaterialVariation::class,
                    'material_variation',
                    '
                        material_variation.offer = material_offer.id AND 
                        material_variation.const = :material_variation_const'
                )
                ->setParameter('material_variation_const', $this->variation, MaterialVariationConst::TYPE);
        }
        else
        {
            $dbal
                ->leftJoin(
                    'material_offer',
                    MaterialVariation::class,
                    'material_variation',
                    'material_variation.offer = material_offer.id'
                );
        }

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.const as material_variation_const')
            ->addSelect('material_variation.value as material_variation_value');


        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = material_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_offer_variation_trans.name as material_variation_name')
            ->leftJoin(
                'category_offer_variation',
                CategoryMaterialVariationTrans::class,
                'category_offer_variation_trans',
                'category_offer_variation_trans.variation = category_offer_variation.id AND category_offer_variation_trans.local = :local'
            );


        /**
         * Модификация множественного варианта торгового предложения
         */
        if(false !== $this->modification)
        {
            $dbal
                ->join(
                    'material_variation',
                    MaterialModification::class,
                    'material_modification',
                    '   
                        material_modification.variation = material_variation.id AND 
                        material_modification.const = :material_modification_const'
                )
                ->setParameter(
                    'material_modification_const',
                    $this->modification,
                    MaterialModificationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'material_variation',
                    MaterialModification::class,
                    'material_modification',
                    'material_modification.variation = material_variation.id'
                );
        }

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.const as material_modification_const')
            ->addSelect('material_modification.value as material_modification_value');


        /* Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_offer_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_offer_modification',
                'category_offer_modification.id = material_modification.category_modification'
            );

        /* Получаем название типа модификации */
        $dbal
            ->addSelect('category_offer_modification_trans.name as material_modification_name')
            ->leftJoin(
                'category_offer_modification',
                CategoryMaterialModificationTrans::class,
                'category_offer_modification_trans',
                'category_offer_modification_trans.modification = category_offer_modification.id AND category_offer_modification_trans.local = :local'
            );


        /* Артикул сырья */
        $dbal->addSelect('
			COALESCE(
                material_modification.article,
                material_variation.article,
                material_offer.article,
                material_info.article
            ) AS material_article
		');

        /**
         *  Фото сырья
         */

        $dbal->leftJoin(
            'material',
            MaterialPhoto::class,
            'material_photo',
            'material_photo.event = material.event AND material_photo.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            'material_offer_images.offer = material_offer.id AND material_offer_images.root = true'
        );

        $dbal->leftJoin(
            'material_variation',
            MaterialVariationImage::class,
            'material_variation_image',
            'material_variation_image.variation = material_variation.id AND material_variation_image.root = true'
        );

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            'material_modification_image.modification = material_modification.id AND material_modification_image.root = true'
        );

        $dbal->addSelect(
            "
			CASE
			
			   WHEN material_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialModificationImage::class)."' , '/', material_modification_image.name)
			   
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialVariationImage::class)."' , '/', material_variation_image.name)
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialOfferImage::class)."' , '/', material_offer_images.name)
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialPhoto::class)."' , '/', material_photo.name)
					
			   ELSE NULL
			   
			END AS material_image
		"
        );

        /** Расширение изображения */
        $dbal->addSelect('
            COALESCE(
                material_modification_image.ext,
                material_variation_image.ext,
                material_offer_images.ext,
                material_photo.ext
            ) AS material_image_ext
        ');


        /** Флаг загрузки файла CDN */
        $dbal->addSelect('
            COALESCE(
                material_modification_image.cdn,
                material_variation_image.cdn,
                material_offer_images.cdn,
                material_photo.cdn
            ) AS material_image_cdn
        ');


        /* Категория */
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

        //        $dbal
        //            ->addSelect('category_info.url AS category_url')
        //            ->leftJoin(
        //                'category',
        //                CategoryMaterialInfo::class,
        //                'category_info',
        //                'category_info.event = category.event'
        //            );

        //        $dbal->leftJoin(
        //            'category',
        //            CategoryMaterialSection::class,
        //            'category_section',
        //            'category_section.event = category.event'
        //        );

        /* Свойства, участвующие в карточке */
        //        $dbal->leftJoin(
        //            'category_section',
        //            CategoryMaterialSectionField::class,
        //            'category_section_field',
        //            'category_section_field.section = category_section.id AND (category_section_field.public = TRUE OR category_section_field.name = TRUE )'
        //        );

        //        $dbal->leftJoin(
        //            'category_section_field',
        //            CategoryMaterialSectionFieldTrans::class,
        //            'category_section_field_trans',
        //            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        //        );

        //        $dbal->leftJoin(
        //            'category_section_field',
        //            MaterialProperty::class,
        //            'material_property',
        //            'material_property.event = material.event AND material_property.field = category_section_field.const'
        //        );

        //        $dbal->addSelect(
        //            "JSON_AGG (DISTINCT
        //
        //                    JSONB_BUILD_OBJECT
        //                    (
        //                        '0', category_section_field.sort, /* сортировка  */
        //
        //                        'field_uid', category_section_field.id,
        //                        'field_const', category_section_field.const,
        //                        'field_name', category_section_field.name,
        //                        'field_alternative', category_section_field.alternative,
        //                        'field_public', category_section_field.public,
        //                        'field_card', category_section_field.card,
        //                        'field_type', category_section_field.type,
        //                        'field_trans', category_section_field_trans.name,
        //                        'field_value', material_property.value
        //                    )
        //            )
        //			AS category_section_field"
        //        );


        /* Наличие и резерв торгового предложения */
        $dbal
            ->leftJoin(
                'material_offer',
                MaterialOfferQuantity::class,
                'material_offer_quantity',
                'material_offer_quantity.offer = material_offer.id'
            );

        /* Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'category_offer_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );

        /* Наличие и резерв модификации множественного варианта */
        $dbal->leftJoin(
            'category_offer_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_modification.id'
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
        );


        /** Стоимость сырья */

        /* Цена торгового предположения */
        $dbal
            ->leftJoin(
                'material_offer',
                MaterialOfferPrice::class,
                'material_offer_price',
                'material_offer_price.offer = material_offer.id'
            );

        /* Цена множественного варианта */
        $dbal
            ->leftJoin(
                'material_variation',
                MaterialVariationPrice::class,
                'material_variation_price',
                'material_variation_price.variation = material_variation.id'
            );

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        );

        $dbal->addSelect('
            COALESCE(
                material_modification_price.price,
                material_variation_price.price,
                material_offer_price.price,
                material_price.price
            ) AS material_price
        ');


        $dbal->addSelect('
            COALESCE(
                material_modification_price.currency,
                material_variation_price.currency,
                material_offer_price.currency,
                material_price.currency
            ) AS material_currency
        ');

        $dbal->allGroupByExclude();

        $result = $dbal
            ->enableCache('materials-catalog')
            ->fetchAssociative();

        return empty($result) ? false : $result;

    }
}
