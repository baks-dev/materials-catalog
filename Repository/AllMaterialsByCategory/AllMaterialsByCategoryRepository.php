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

namespace BaksDev\Materials\Catalog\Repository\AllMaterialsByCategory;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Core\Type\Device\Device;
use BaksDev\Core\Type\Device\Devices\Desktop;
use BaksDev\DeliveryTransport\BaksDevDeliveryTransportBundle;
use BaksDev\DeliveryTransport\Entity\MaterialParameter\DeliveryPackageMaterialParameter;
use BaksDev\Materials\Catalog\Entity\Active\MaterialActive;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Description\MaterialDescription;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Modify\MaterialModify;
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
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Forms\MaterialCategoryFilter\User\MaterialCategoryFilterDTO;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Event\CategoryMaterialEvent;
use BaksDev\Materials\Category\Entity\Info\CategoryMaterialInfo;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Section\CategoryMaterialSection;
use BaksDev\Materials\Category\Entity\Section\Field\CategoryMaterialSectionField;
use BaksDev\Materials\Category\Entity\Section\Field\Trans\CategoryMaterialSectionFieldTrans;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Materials\Category\Type\Section\Field\Id\CategoryMaterialSectionFieldUid;
use InvalidArgumentException;

final class AllMaterialsByCategoryRepository implements AllMaterialsByCategoryInterface
{

    private ?array $property = null;

    private CategoryMaterialUid|false $category = false;


    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}


    public function property(?array $property): self
    {
        if(empty($property))
        {
            return $this;
        }

        $this->property = $property;

        return $this;
    }

    public function category(CategoryMaterial|CategoryMaterialUid|string $category): self
    {
        if(is_string($category))
        {
            $category = new CategoryMaterialUid($category);
        }

        if($category instanceof CategoryMaterial)
        {
            $category = $category->getId();
        }

        $this->category = $category;

        return $this;
    }


    private function builder(string $expr = 'AND'): DBALQueryBuilder
    {
        if(false === $this->category)
        {
            throw new InvalidArgumentException('Invalid Argument category');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(CategoryMaterial::class, 'category')
            ->where('category.id = :category')
            ->setParameter('category', $this->category, CategoryMaterialUid::TYPE);

        $dbal->leftJoin(
            'category',
            CategoryMaterialEvent::class,
            'category_event',
            'category_event.id = category.event OR category_event.parent = category.id'
        );


        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category_event',
                CategoryMaterialInfo::class,
                'category_info',
                'category_info.event = category_event.id'
            );


        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category_event',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category_event.id AND category_trans.local = :local'
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
            'category_section_field.section = category_section.id AND (category_section_field.card = TRUE OR category_section_field.photo = TRUE OR category_section_field.name = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryMaterialSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );


        /** Категория сырья */

        $dbal
            ->leftJoin(
                'category',
                MaterialCategory::class,
                'material_category',
                'material_category.category = category_event.category'
            );


        $dbal
            ->addSelect('material.id')
            ->addSelect('material.event')
            ->join(
                'material_category',
                Material::class,
                'material',
                'material.event = material_category.event'
            );


        $dbal
            ->addSelect('material_active.active_from')
            ->join(
                'material',
                MaterialActive::class,
                'material_active',
                '
                    material_active.event = material.event AND 
                    material_active.active IS TRUE AND
                    (material_active.active_to IS NULL OR material_active.active_to > NOW())
                ');

        $dbal->leftJoin(
            'material',
            MaterialEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        /** ФИЛЬТР СВОЙСТВ */
        if($this->property)
        {
            if($expr === 'AND')
            {
                foreach($this->property as $type => $item)
                {
                    if($item === true)
                    {
                        $item = 'true';
                    }

                    $prepareKey = uniqid('key_', false);
                    $prepareValue = uniqid('val_', false);
                    $aliase = uniqid('aliase', false);

                    $MaterialCategorySectionFieldUid = new CategoryMaterialSectionFieldUid($type);
                    $MaterialPropertyJoin = $aliase.'.field = :'.$prepareKey.' AND '.$aliase.'.value = :'.$prepareValue;

                    $dbal->setParameter(
                        $prepareKey,
                        $MaterialCategorySectionFieldUid,
                        CategoryMaterialSectionFieldUid::TYPE
                    );
                    $dbal->setParameter($prepareValue, $item);

                    $dbal->join(
                        'material',
                        MaterialProperty::class,
                        $aliase,
                        $aliase.'.event = material.event '.$expr.' '.$MaterialPropertyJoin
                    );
                }
            }
            else
            {

                foreach($this->property as $type => $item)
                {
                    if($item === true)
                    {
                        $item = 'true';
                    }

                    $prepareKey = uniqid('', false);
                    $prepareValue = uniqid('', false);

                    $MaterialCategorySectionFieldUid = new CategoryMaterialSectionFieldUid($type);
                    $MaterialPropertyJoin[] = 'material_property_filter.field = :'.$prepareKey.' AND material_property_filter.value = :'.$prepareValue;

                    $dbal->setParameter(
                        $prepareKey,
                        $MaterialCategorySectionFieldUid,
                        CategoryMaterialSectionFieldUid::TYPE
                    );
                    $dbal->setParameter($prepareValue, $item);

                }

                $dbal->join(
                    'material',
                    MaterialProperty::class,
                    'material_property_filter',
                    'material_property_filter.event = material.event AND '.implode(' '.$expr.' ', $MaterialPropertyJoin)
                );
            }
        }


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
            )
            ->setParameter('device', 'pc');


        /** Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        )
            ->addGroupBy('material_price.price')
            ->addGroupBy('material_price.currency');

        /* MaterialInfo */

        $dbal
            ->addSelect('material_info.sort')
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
            );

        /**
         * Торговое предложение
         */

        $method = 'leftJoin';

        if($this->filter?->getOffer())
        {
            $method = 'join';
            $dbal->setParameter('offer', $this->filter->getOffer());
        }


        $dbal->{$method}(
            'material',
            MaterialOffer::class,
            'material_offer',
            'material_offer.event = material.event '.($this->filter?->getOffer() ? ' AND material_offer.value = :offer' : '').' '
        );

        /*  тип торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            CategoryMaterialOffers::class,
            'category_offer',
            'category_offer.id = material_offer.category_offer'
        );


        /* Цена торгового предожения */
        $dbal
            ->leftJoin(
                'material_offer',
                MaterialOfferPrice::class,
                'material_offer_price',
                'material_offer_price.offer = material_offer.id'
            )
            ->addGroupBy('material_offer_price.currency');


        $dbal
            ->addSelect('SUM(material_offer_quantity.quantity) AS material_offer_quantity')
            ->leftJoin(
                'material_offer',
                MaterialOfferQuantity::class,
                'material_offer_quantity',
                'material_offer_quantity.offer = material_offer.id'
            );


        /**
         * Множественные варианты торгового предложения
         */

        $method = 'leftJoin';
        if($this->filter?->getVariation())
        {
            $method = 'join';
            $dbal->setParameter('variation', $this->filter->getVariation());
        }

        $dbal->{$method}(
            'material_offer',
            MaterialVariation::class,
            'material_variation',
            'material_variation.offer = material_offer.id '.($this->filter?->getVariation() ? ' AND material_variation.value = :variation' : '').' '
        );

        /* тип множественного варианта */

        $dbal->leftJoin(
            'material_variation',
            CategoryMaterialVariation::class,
            'category_variation',
            'category_variation.id = material_variation.category_variation'
        );


        /* Цена множественного варианта */

        $dbal->leftJoin(
            'category_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        )
            ->addGroupBy('material_variation_price.currency');


        $dbal->allGroupByExclude();

        $dbal
            ->addSelect('SUM(material_variation_quantity.quantity) AS material_variation_quantity')
            ->leftJoin(
                'category_variation',
                MaterialsVariationQuantity::class,
                'material_variation_quantity',
                'material_variation_quantity.variation = material_variation.id'
            );


        /**
         * Модификация множественного варианта торгового предложения
         */

        $method = 'leftJoin';
        if($this->filter?->getModification())
        {
            $method = 'join';
            $dbal->setParameter('modification', $this->filter->getModification());
        }

        $dbal->{$method}(
            'material_variation',
            MaterialModification::class,
            'material_modification',
            'material_modification.variation = material_variation.id '.($this->filter?->getModification() ? ' AND material_modification.value = :modification' : '').' '
        );

        /* тип модификации множественного варианта */

        $dbal->leftJoin(
            'material_modification',
            CategoryMaterialModification::class,
            'category_modification',
            'category_modification.id = material_modification.category_modification'
        );


        /* Цена множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        )
            ->addGroupBy('material_modification_price.currency');


        $dbal
            ->addSelect('SUM(material_modification_quantity.quantity) AS material_modification_quantity')
            ->leftJoin(
                'material_modification',
                MaterialModificationQuantity::class,
                'material_modification_quantity',
                'material_modification_quantity.modification = material_modification.id'
            )
            ->addGroupBy('material_modification_price.currency');


        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
				
					JSONB_BUILD_OBJECT
					(
						
						/* свойства для сортировки JSON */
						'0', CONCAT(material_offer.value, material_variation.value, material_modification.value),
						
					
						'offer_uid', material_offer.id, /* значение торгового предложения */
						'offer_value', material_offer.value, /* значение торгового предложения */
						'offer_reference', category_offer.reference, /* тип (field) торгового предложения */
						'offer_article', material_offer.article, /* артикул торгового предложения */

						'variation_uid', material_variation.id, /* значение множественного варианта */
						'variation_value', material_variation.value, /* значение множественного варианта */
						'variation_reference', category_variation.reference, /* тип (field) множественного варианта */
						'variation_article', category_variation.article, /* валюта множественного варианта */

						'modification_uid', material_modification.id, /* значение модификации */
						'modification_value', material_modification.value, /* значение модификации */
						'modification_reference', category_modification.reference, /* тип (field) модификации */
						'modification_article', category_modification.article /* артикул модификации */

					)
				
			)
			AS material_offers"
        );


        /** Фото сырья */

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            '
                material_modification_image.modification = material_modification.id AND
                material_modification_image.root = true
			'
        );


        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            '
                material_variation_image.variation = material_variation.id AND
                material_variation_image.root = true
			');


        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            '
                material_variation_image.name IS NULL AND
                material_offer_images.offer = material_offer.id AND
                material_offer_images.root = true
			');


        $dbal->leftJoin(
            'material_offer',
            MaterialPhoto::class,
            'material_photo',
            '
                material_offer_images.name IS NULL AND
                material_photo.event = material.event AND
                material_photo.root = true
			');

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


        /** Минимальная стоимость сырья */

        $dbal->addSelect("CASE
                          
                   
                   /* СТОИМОСТЬ МОДИФИКАЦИИ */       
        WHEN (ARRAY_AGG(
                            DISTINCT material_modification_price.price ORDER BY material_modification_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_modification_price.price > 0
                         )
                     )[1] > 0 
                     
                     THEN (ARRAY_AGG(
                            DISTINCT material_modification_price.price ORDER BY material_modification_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_modification_price.price > 0
                         )
                     )[1]
         
         
         /* СТОИМОСТЬ ВАРИАНТА */
         WHEN (ARRAY_AGG(
                            DISTINCT material_variation_price.price ORDER BY material_variation_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_variation_price.price > 0
                         )
                     )[1] > 0 
                     
         THEN (ARRAY_AGG(
                            DISTINCT material_variation_price.price ORDER BY material_variation_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_variation_price.price > 0
                         )
                     )[1]
         
         
         /* СТОИМОСТЬ ТП */
            WHEN (ARRAY_AGG(
                            DISTINCT material_offer_price.price ORDER BY material_offer_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_offer_price.price > 0
                         )
                     )[1] > 0 
                     
            THEN (ARRAY_AGG(
                            DISTINCT material_offer_price.price ORDER BY material_offer_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_offer_price.price > 0
                         )
                     )[1]
         
			  
			   WHEN material_price.price IS NOT NULL 
			   THEN material_price.price
			   
			   ELSE NULL
			END AS material_price
		");


        /** Валюта сырья */
        $dbal->addSelect(
            "
			CASE
			
			   WHEN MIN(material_modification_price.price) IS NOT NULL AND MIN(material_modification_price.price) > 0 
			   THEN material_modification_price.currency
			   
			   WHEN MIN(material_variation_price.price) IS NOT NULL AND MIN(material_variation_price.price) > 0  
			   THEN material_variation_price.currency
			   
			   WHEN MIN(material_offer_price.price) IS NOT NULL AND MIN(material_offer_price.price) > 0 
			   THEN material_offer_price.currency
			   
			   WHEN material_price.price IS NOT NULL 
			   THEN material_price.currency
			   
			   ELSE NULL
			   
			END AS material_currency
		"
        );

        /** Количественный учет */
        $dbal->addSelect("
			CASE
			
			   WHEN SUM(material_modification_quantity.quantity) > 0 
			   THEN SUM(material_modification_quantity.quantity)
					
			   WHEN SUM(material_variation_quantity.quantity) > 0 
			   THEN SUM(material_variation_quantity.quantity)
					
			   WHEN SUM(material_offer_quantity.quantity) > 0 
			   THEN SUM(material_offer_quantity.quantity)
					
			   WHEN SUM(material_price.quantity) > 0 
			   THEN SUM(material_price.quantity)
			   
			   ELSE 0
			   
			END AS material_quantity
		");


        $dbal->leftJoin(
            'material',
            MaterialProperty::class,
            'material_property',
            'material_property.event = material.event AND material_property.field = category_section_field.const'
        );


        $dbal->addSelect("JSON_AGG ( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
					'0', category_section_field.sort,
					'field_name', category_section_field.name,
					'field_card', category_section_field.card,
					'field_photo', category_section_field.photo,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', material_property.value
				)
			
		)
			AS category_section_field"
        );

        $dbal->addOrderBy('material_info.sort', 'DESC');

        $dbal->allGroupByExclude();

        return $dbal;
    }

    public function analyze(string $expr = 'AND'): void
    {
        $this->builder()->analyze();
    }

    public function find(string $expr = 'AND'): array|false
    {
        return $this->builder()->fetchAllAssociative();
    }


    public function findPaginator(string $expr = 'AND'): PaginatorInterface
    {
        $dbal = $this->builder();
        return $this->paginator->fetchAllAssociative($dbal);
    }


    /** DEPRICATE */


    public function fetchAllMaterialByCategoryAssociative(
        CategoryMaterialUid|string $category,
        string $expr = 'AND',
    ): PaginatorInterface
    {

        if(is_string($category))
        {
            $category = new CategoryMaterialUid($category);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(CategoryMaterial::class, 'category')
            ->where('category.id = :category')
            ->setParameter('category', $category, CategoryMaterialUid::TYPE);

        $dbal->join(
            'category',
            CategoryMaterialEvent::class,
            'category_event',
            'category_event.id = category.event OR category_event.parent = category.id'
        );


        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'category_event',
                CategoryMaterialInfo::class,
                'category_info',
                'category_info.event = category_event.id'
            );


        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category_event',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category_event.id AND category_trans.local = :local'
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
            'category_section_field.section = category_section.id AND (category_section_field.card = TRUE OR category_section_field.photo = TRUE OR category_section_field.name = TRUE )'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryMaterialSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id AND category_section_field_trans.local = :local'
        );


        /** Категория сырья */

        $dbal
            ->leftJoin(
                'category',
                MaterialCategory::class,
                'material_category',
                'material_category.category = category_event.category'
            );


        $dbal
            ->addSelect('material.id')
            ->addSelect('material.event')
            ->join(
                'material_category',
                Material::class,
                'material',
                'material.event = material_category.event'
            );

        $dbal
            ->addSelect('material_active.active_from')
            ->join(
                'material',
                MaterialActive::class,
                'material_active',
                '
                    material_active.event = material.event AND 
                    material_active.active IS TRUE AND
                    (material_active.active_to IS NULL OR material_active.active_to > NOW())
                ');

        $dbal->leftJoin(
            'material',
            MaterialEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        /** ФИЛЬТР СВОЙСТВ */
        if($this->property)
        {
            if($expr === 'AND')
            {
                foreach($this->property as $type => $item)
                {
                    if($item === true)
                    {
                        $item = 'true';
                    }

                    $prepareKey = uniqid('key_', false);
                    $prepareValue = uniqid('val_', false);
                    $aliase = uniqid('aliase', false);

                    $MaterialCategorySectionFieldUid = new CategoryMaterialSectionFieldUid($type);
                    $MaterialPropertyJoin = $aliase.'.field = :'.$prepareKey.' AND '.$aliase.'.value = :'.$prepareValue;

                    $dbal->setParameter(
                        $prepareKey,
                        $MaterialCategorySectionFieldUid,
                        CategoryMaterialSectionFieldUid::TYPE
                    );
                    $dbal->setParameter($prepareValue, $item);

                    $dbal->join(
                        'material',
                        MaterialProperty::class,
                        $aliase,
                        $aliase.'.event = material.event '.$expr.' '.$MaterialPropertyJoin
                    );
                }
            }
            else
            {

                foreach($this->property as $type => $item)
                {
                    if($item === true)
                    {
                        $item = 'true';
                    }

                    $prepareKey = uniqid('', false);
                    $prepareValue = uniqid('', false);

                    $MaterialCategorySectionFieldUid = new CategoryMaterialSectionFieldUid($type);
                    $MaterialPropertyJoin[] = 'material_property_filter.field = :'.$prepareKey.' AND material_property_filter.value = :'.$prepareValue;

                    $dbal->setParameter(
                        $prepareKey,
                        $MaterialCategorySectionFieldUid,
                        CategoryMaterialSectionFieldUid::TYPE
                    );
                    $dbal->setParameter($prepareValue, $item);

                }

                $dbal->join(
                    'material',
                    MaterialProperty::class,
                    'material_property_filter',
                    'material_property_filter.event = material.event AND '.implode(' '.$expr.' ', $MaterialPropertyJoin)
                );
            }
        }


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

        /** Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        )
            ->addGroupBy('material_price.price')
            ->addGroupBy('material_price.currency');

        /* MaterialInfo */

        $dbal
            ->addSelect('material_info.sort')
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
            );

        /**
         * Торговое предложение
         */

        $method = 'leftJoin';

        if($this->filter?->getOffer())
        {
            $method = 'join';
            $dbal->setParameter('offer', $this->filter->getOffer());
        }

        $dbal->{$method}(
            'material',
            MaterialOffer::class,
            'material_offer',
            'material_offer.event = material.event '.($this->filter?->getOffer() ? ' AND material_offer.value = :offer' : '').' '
        );

        /*  тип торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            CategoryMaterialOffers::class,
            'category_offer',
            'category_offer.id = material_offer.category_offer'
        );


        /* Цена торгового предожения */
        $dbal
            ->leftJoin(
                'material_offer',
                MaterialOfferPrice::class,
                'material_offer_price',
                'material_offer_price.offer = material_offer.id'
            )
            ->addGroupBy('material_offer_price.currency');


        $dbal
            ->addSelect('SUM(material_offer_quantity.quantity) AS material_offer_quantity')
            ->leftJoin(
                'material_offer',
                MaterialOfferQuantity::class,
                'material_offer_quantity',
                'material_offer_quantity.offer = material_offer.id'
            );


        /**
         * Множественные варианты торгового предложения
         */

        $method = 'leftJoin';
        if($this->filter?->getVariation())
        {
            $method = 'join';
            $dbal->setParameter('variation', $this->filter->getVariation());
        }


        $dbal->{$method}(
            'material_offer',
            MaterialVariation::class,
            'material_variation',
            'material_variation.offer = material_offer.id '.($this->filter?->getVariation() ? ' AND material_variation.value = :variation' : '').' '
        );

        /* тип множественного варианта */

        $dbal->leftJoin(
            'material_variation',
            CategoryMaterialVariation::class,
            'category_variation',
            'category_variation.id = material_variation.category_variation'
        );

        /* Цена множественного варианта */

        $dbal->leftJoin(
            'category_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        )
            ->addGroupBy('material_variation_price.currency');


        $dbal
            ->addSelect('SUM(material_variation_quantity.quantity) AS material_variation_quantity')
            ->leftJoin(
                'category_variation',
                MaterialsVariationQuantity::class,
                'material_variation_quantity',
                'material_variation_quantity.variation = material_variation.id'
            );


        /**
         * Модификация множественного варианта торгового предложения
         */

        $method = 'leftJoin';
        if($this->filter?->getModification())
        {
            $method = 'join';
            $dbal->setParameter('modification', $this->filter->getModification());
        }

        $dbal->{$method}(
            'material_variation',
            MaterialModification::class,
            'material_modification',
            'material_modification.variation = material_variation.id '.($this->filter?->getModification() ? ' AND material_modification.value = :modification' : '').' '
        );

        /* тип модификации множественного варианта */

        $dbal->leftJoin(
            'material_modification',
            CategoryMaterialModification::class,
            'category_modification',
            'category_modification.id = material_modification.category_modification'
        );


        /* Цена множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        )
            ->addGroupBy('material_modification_price.currency');


        $dbal
            ->addSelect('SUM(material_modification_quantity.quantity) AS material_modification_quantity')
            ->leftJoin(
                'material_modification',
                MaterialModificationQuantity::class,
                'material_modification_quantity',
                'material_modification_quantity.modification = material_modification.id'
            )
            ->addGroupBy('material_modification_price.currency');


        $dbal->addSelect(
            "JSON_AGG
			( DISTINCT
				
					JSONB_BUILD_OBJECT
					(
						
						/* свойства для сортирвоки JSON */
						'0', CONCAT(material_offer.value, material_variation.value, material_modification.value),
						
						
						'offer_uid', material_offer.id, /* значение торгового предложения */
						'offer_value', material_offer.value, /* значение торгового предложения */
						'offer_reference', category_offer.reference, /* тип (field) торгового предложения */
						'offer_article', material_offer.article, /* артикул торгового предложения */

						'variation_uid', material_variation.id, /* значение множественного варианта */
						'variation_value', material_variation.value, /* значение множественного варианта */
						'variation_reference', category_variation.reference, /* тип (field) множественного варианта */
						'variation_article', category_variation.article, /* валюта множественного варианта */

						'modification_uid', material_modification.id, /* значение модификации */
						'modification_value', material_modification.value, /* значение модификации */
						'modification_reference', category_modification.reference, /* тип (field) модификации */
						'modification_article', category_modification.article /* артикул модификации */

					)
				
			)
			AS material_offers"
        );


        /** Фото сырья */

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            '
                material_modification_image.modification = material_modification.id AND
                material_modification_image.root = true
			'
        );


        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            '
                material_variation_image.variation = material_variation.id AND
                material_variation_image.root = true
			');


        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            '
                material_variation_image.name IS NULL AND
                material_offer_images.offer = material_offer.id AND
                material_offer_images.root = true
			');


        $dbal->leftJoin(
            'material_offer',
            MaterialPhoto::class,
            'material_photo',
            '
                material_offer_images.name IS NULL AND
                material_photo.event = material.event AND
                material_photo.root = true
			');

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

        /** Флаг загрузки файла CDN */
        $dbal->addSelect(
            "
			CASE
               WHEN material_modification_image.name IS NOT NULL 
               THEN material_modification_image.ext
					
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.ext
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.ext
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.ext
					
			   ELSE NULL
			END AS material_image_ext
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect(
            "
			CASE
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.cdn
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.cdn
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.cdn
					
			   ELSE NULL
			END AS material_image_cdn
		"
        );


        /** Минимальная стоимость сырья */

        $dbal->addSelect("CASE
                          
                   
                   /* СТОИМОСТЬ МОДИФИКАЦИИ */       
        WHEN (ARRAY_AGG(
                            DISTINCT material_modification_price.price ORDER BY material_modification_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_modification_price.price > 0
                         )
                     )[1] > 0 
                     
                     THEN (ARRAY_AGG(
                            DISTINCT material_modification_price.price ORDER BY material_modification_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_modification_price.price > 0
                         )
                     )[1]
         
         
         /* СТОИМОСТЬ ВАРИАНТА */
         WHEN (ARRAY_AGG(
                            DISTINCT material_variation_price.price ORDER BY material_variation_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_variation_price.price > 0
                         )
                     )[1] > 0 
                     
         THEN (ARRAY_AGG(
                            DISTINCT material_variation_price.price ORDER BY material_variation_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_variation_price.price > 0
                         )
                     )[1]
         
         
         /* СТОИМОСТЬ ТП */
            WHEN (ARRAY_AGG(
                            DISTINCT material_offer_price.price ORDER BY material_offer_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_offer_price.price > 0
                         )
                     )[1] > 0 
                     
            THEN (ARRAY_AGG(
                            DISTINCT material_offer_price.price ORDER BY material_offer_price.price
                         ) 
                         FILTER 
                         (
                            WHERE  material_offer_price.price > 0
                         )
                     )[1]
         
			  
			   WHEN material_price.price IS NOT NULL 
			   THEN material_price.price
			   
			   ELSE NULL
			END AS material_price
		");


        /** Валюта сырья */
        $dbal->addSelect(
            "
			CASE
			
			   WHEN MIN(material_modification_price.price) IS NOT NULL AND MIN(material_modification_price.price) > 0 
			   THEN material_modification_price.currency
			   
			   WHEN MIN(material_variation_price.price) IS NOT NULL AND MIN(material_variation_price.price) > 0  
			   THEN material_variation_price.currency
			   
			   WHEN MIN(material_offer_price.price) IS NOT NULL AND MIN(material_offer_price.price) > 0 
			   THEN material_offer_price.currency
			   
			   WHEN material_price.price IS NOT NULL 
			   THEN material_price.currency
			   
			   ELSE NULL
			   
			END AS material_currency
		"
        );

        /** Количественный учет */
        $dbal->addSelect("
			CASE
			
			   WHEN SUM(material_modification_quantity.quantity) > 0 
			   THEN SUM(material_modification_quantity.quantity)
					
			   WHEN SUM(material_variation_quantity.quantity) > 0 
			   THEN SUM(material_variation_quantity.quantity)
					
			   WHEN SUM(material_offer_quantity.quantity) > 0 
			   THEN SUM(material_offer_quantity.quantity)
					
			   WHEN SUM(material_price.quantity) > 0 
			   THEN SUM(material_price.quantity)
			   
			   ELSE 0
			   
			END AS material_quantity
		");


        $dbal->leftJoin(
            'material',
            MaterialProperty::class,
            'material_property',
            'material_property.event = material.event AND material_property.field = category_section_field.const'
        );


        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
					'0', category_section_field.sort,
					'field_name', category_section_field.name,
					'field_card', category_section_field.card,
					'field_photo', category_section_field.photo,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', material_property.value
				)
			
		)
			AS category_section_field"
        );

        $dbal->addOrderBy('material_info.sort', 'DESC');

        $dbal->allGroupByExclude();
        return $this->paginator->fetchAllAssociative($dbal);

    }


    /** Метод возвращает все товары в категории */
    public function fetchAllMaterialByCategory(
        ?CategoryMaterialUid $category = null
    ): array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->addSelect('material_category.category');

        if($category)
        {
            $dbal
                ->from(MaterialCategory::class, 'material_category')
                ->where('material_category.category = :category AND material_category.root = true')
                ->setParameter('category', $category, CategoryMaterialUid::TYPE);

            $dbal->join(
                'material_category',
                Material::class,
                'material',
                'material.event = material_category.event'
            );
        }
        else
        {
            $dbal->from(Material::class, 'material');

            $dbal->leftJoin(
                'material',
                MaterialCategory::class,
                'material_category',
                'material_category.event = material.event AND material_category.root = true'
            );
        }

        $dbal->addSelect('material.id');


        $dbal
            ->addSelect('material_info.sort')
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
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
            ->addSelect('material_desc.preview')
            ->leftJoin(
                'material',
                MaterialDescription::class,
                'material_desc',
                'material_desc.event = material.event AND material_desc.local = :local AND material_desc.device = :device'
            )->setParameter('device', new Device(Desktop::class), Device::TYPE);

        $dbal
            ->addSelect('material_modify.mod_date AS modify')
            ->leftJoin(
                'material',
                MaterialModify::class,
                'material_modify',
                'material_modify.event = material.event'
            );


        /** Торговое предложение */
        $dbal
            ->addSelect('material_offer.value AS offer_value')
            ->leftJoin(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event'
            );

        /* Цена торгового предожения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferPrice::class,
            'material_offer_price',
            'material_offer_price.offer = material_offer.id'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        /* Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference as offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );


        /** Множественный вариант */
        $dbal
            ->addSelect('material_variation.value AS variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id'
            );


        $dbal->leftJoin(
            'material_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        );

        $dbal->leftJoin(
            'category_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );

        $dbal
            ->addSelect('category_variation.reference as variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );


        /** Модификация множественного варианта торгового предложения */
        $dbal
            ->addSelect('material_modification.value AS modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id'
            );

        /** Цена множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        );

        $dbal->leftJoin(
            'category_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_modification.id'
        );

        /** Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
            );


        /** Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );


        /** Идентификатор */
        $dbal->addSelect(
            "
			CASE
			   WHEN material_modification.const IS NOT NULL 
			   THEN material_modification.const
			   
			   WHEN material_variation.const IS NOT NULL 
			   THEN material_variation.const
			   
			   WHEN material_offer.const IS NOT NULL 
			   THEN material_offer.const
			   
			   ELSE material.id
			END AS material_id
		"
        );


        /** Стоимость */
        $dbal->addSelect('
			COALESCE(
                NULLIF(material_modification_price.price, 0), 
                NULLIF(material_variation_price.price, 0), 
                NULLIF(material_offer_price.price, 0), 
                NULLIF(material_price.price, 0),
                0
            ) AS material_price
		');


        /** Валюта сырья */
        $dbal->addSelect(
            "
			CASE
			   WHEN material_modification_price.price IS NOT NULL AND material_modification_price.price > 0 
			   THEN material_modification_price.currency
			   
			   WHEN material_variation_price.price IS NOT NULL AND material_variation_price.price > 0  
			   THEN material_variation_price.currency
			   
			   WHEN material_offer_price.price IS NOT NULL AND material_offer_price.price > 0 
			   THEN material_offer_price.currency
			   
			   WHEN material_price.price IS NOT NULL 
			   THEN material_price.currency
			   
			   ELSE NULL
			END AS material_currency
		"
        )
            ->addGroupBy('material_modification_price.currency')
            ->addGroupBy('material_variation_price.currency')
            ->addGroupBy('material_offer_price.currency')
            ->addGroupBy('material_price.currency');


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

        /** Фото сырья */

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            '
			material_modification_image.modification = material_modification.id AND
			material_modification_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            '
			material_variation_image.variation = material_variation.id AND
			material_variation_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            '
			material_variation_image.name IS NULL AND
			material_offer_images.offer = material_offer.id AND
			material_offer_images.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialPhoto::class,
            'material_photo',
            '
			material_offer_images.name IS NULL AND
			material_photo.event = material.event AND
			material_photo.root = true
			'
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

        /** Расширение файла */
        $dbal->addSelect(
            "
			CASE
                WHEN material_modification_image.ext IS NOT NULL AND material_modification_image.name IS NOT NULL 
                THEN material_modification_image.ext
					
			   WHEN material_variation_image.ext IS NOT NULL AND material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.ext
					
			   WHEN material_offer_images.ext IS NOT NULL AND material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.ext
					
			   WHEN material_photo.ext IS NOT NULL AND material_photo.name IS NOT NULL 
			   THEN material_photo.ext
					
			   ELSE NULL
			END AS material_image_ext
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect(
            "
			CASE
			    WHEN material_modification_image.cdn IS NOT NULL AND material_modification_image.name IS NOT NULL 
			    THEN material_modification_image.cdn
					
			   WHEN material_variation_image.cdn IS NOT NULL AND material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.cdn
					
			   WHEN material_offer_images.cdn IS NOT NULL AND material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.cdn
					
			   WHEN material_photo.cdn IS NOT NULL AND material_photo.name IS NOT NULL 
			   THEN material_photo.cdn
					
			   ELSE NULL
			END AS material_image_cdn
		"
        );


        /** Свойства, учавствующие в карточке */

        $dbal->leftJoin(
            'material_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_category.category'
        );


        $dbal
            ->leftJoin(
                'category',
                CategoryMaterialInfo::class,
                'category_info',
                'category_info.event = category.event'
            );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->addSelect('category_trans.description AS category_desc')
            ->leftJoin(
                'category',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );

        $dbal->leftJoin(
            'category',
            CategoryMaterialSection::class,
            'category_section',
            'category_section.event = category.event'
        );


        $dbal->leftJoin(
            'category_section',
            CategoryMaterialSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id 
            AND category_section_field.card = TRUE'
        );

        $dbal->leftJoin(
            'category_section_field',
            CategoryMaterialSectionFieldTrans::class,
            'category_section_field_trans',
            'category_section_field_trans.field = category_section_field.id 
            AND category_section_field_trans.local = :local'
        );


        $dbal->leftJoin(
            'category_section_field',
            MaterialProperty::class,
            'material_property',
            'material_property.event = material.event 
            AND material_property.field = category_section_field.const'
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


        /* Артикул сырья */

        $dbal->addSelect(
            '
			CASE
			   WHEN material_modification.barcode IS NOT NULL 
			   THEN material_modification.barcode
			   
			   WHEN material_variation.barcode IS NOT NULL 
			   THEN material_variation.barcode
			   
			   WHEN material_offer.barcode IS NOT NULL 
			   THEN material_offer.barcode
			   
			   WHEN material_info.barcode IS NOT NULL 
			   THEN material_info.barcode
			   
			   ELSE NULL
			END AS material_barcode
		'
        );


        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT

				JSONB_BUILD_OBJECT
				(
					'0', category_section_field.sort,
					'field_uid', category_section_field.id,
					'field_const', category_section_field.const,
					'field_name', category_section_field.name,
					'field_card', category_section_field.card,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', material_property.value
				)

		)
			AS category_section_field"
        );


        /**  Вес товара  */

        if(class_exists(BaksDevDeliveryTransportBundle::class))
        {

            $dbal
                ->addSelect('material_parameter.length AS material_parameter_length')
                ->addSelect('material_parameter.width AS material_parameter_width')
                ->addSelect('material_parameter.height AS material_parameter_height')
                ->addSelect('material_parameter.weight AS material_parameter_weight')
                ->leftJoin(
                    'material_modification',
                    DeliveryPackageMaterialParameter::class,
                    'material_parameter',
                    'material_parameter.material = material.id AND
            (material_parameter.offer IS NULL OR material_parameter.offer = material_offer.const) AND
            (material_parameter.variation IS NULL OR material_parameter.variation = material_variation.const) AND
            (material_parameter.modification IS NULL OR material_parameter.modification = material_modification.const)

        '
                );
        }

        $dbal->addOrderBy('material_info.sort', 'DESC');

        $dbal->allGroupByExclude();

        return $dbal->enableCache('materials-catalog', 86400)->fetchAllAssociative();

    }


}
