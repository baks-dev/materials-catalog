<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Catalog\Repository\MaterialCatalog;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Active\MaterialActive;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
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
use BaksDev\Materials\Catalog\Entity\Property\MaterialProperty;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Forms\MaterialCategoryFilter\User\MaterialCategoryFilterDTO;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
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

final class MaterialCatalogRepository implements MaterialCatalogInterface
{
    private CategoryMaterialUid|false $categoryUid = false;

    private int|false $maxResult = false;

    private ?MaterialCategoryFilterDTO $filter = null;

    private ?array $property = null;

    public function __construct(
        private readonly DBALQueryBuilder $dbal
    ) {}

    public function filter(MaterialCategoryFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function property(?array $property): self
    {
        if(empty($property))
        {
            return $this;
        }

        $this->property = $property;

        return $this;
    }

    /**
     * Максимальное количество записей в результате
     */
    public function maxResult(int $max): self
    {
        $this->maxResult = $max;

        return $this;
    }

    /**
     * Фильтр по категории
     */
    public function forCategory(CategoryMaterial|CategoryMaterialUid|string $category): self
    {
        if($category instanceof CategoryMaterial)
        {
            $category = $category->getId();
        }

        if(is_string($category))
        {
            $category = new CategoryMaterialUid($category);
        }

        $this->categoryUid = $category;

        return $this;
    }

    /**
     * Метод возвращает список сырья из разных категорий
     *
     * @return array{
     * "id": string,
     * "event": string,
     * "material_name": string,
     * "url": string,
     * "active_from": string,
     * "active_to": string,
     * "material_offer_uid": string,
     * "material_offer_value": string,
     * "material_offer_reference": string,
     * "material_variation_uid": string,
     * "material_variation_value": string,
     * "material_variation_reference": string,
     * "material_modification_uid": string,
     * "material_modification_value": string,
     * "material_modification_reference": string,
     * "material_article": string,
     * "material_image": string,
     * "material_image_ext": string,
     * "material_image_cdn": bool,
     * "material_price": int,
     * "material_currency": string,
     * "category_url": string,
     * "category_name": string,
     * "category_section_field": string,
     * } | false
     */
    public function find(string $expr = 'AND'): array|false
    {

        $dbal = $this->dbal
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material');

        $dbal->join('material',
            MaterialEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        /** Категория */
        if($this->categoryUid instanceof CategoryMaterialUid)
        {
            $dbal->join(
                'material',
                MaterialCategory::class,
                'material_event_category',
                '
                material_event_category.event = material.event AND 
                material_event_category.category = :category AND 
                material_event_category.root = true'
            )->setParameter(
                'category',
                $this->categoryUid,
                CategoryMaterialUid::TYPE
            );
        }
        else
        {
            $dbal->leftJoin(
                'material',
                MaterialCategory::class,
                'material_event_category',
                '
                material_event_category.event = material.event AND 
                material_event_category.root = true'
            );
        }


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
                    $alias = uniqid('alias', false);

                    $MaterialCategorySectionFieldUid = new CategoryMaterialSectionFieldUid($type);
                    $MaterialPropertyJoin = $alias.'.field = :'.$prepareKey.' AND '.$alias.'.value = :'.$prepareValue;

                    $dbal->setParameter(
                        $prepareKey,
                        $MaterialCategorySectionFieldUid,
                        CategoryMaterialSectionFieldUid::TYPE
                    );
                    $dbal->setParameter($prepareValue, $item);

                    $dbal->join(
                        'material',
                        MaterialProperty::class,
                        $alias,
                        $alias.'.event = material.event '.$expr.' '.$MaterialPropertyJoin
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
                'material_event',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material_event.id AND material_trans.local = :local'
            );

        /** Цена товара */
        $dbal
            ->leftJoin(
                'material_event',
                MaterialPrice::class,
                'material_price',
                'material_price.event = material_event.id'
            )
            ->addGroupBy('material_price.price')
            ->addGroupBy('material_price.currency')
            ->addGroupBy('material_price.quantity')
            ->addGroupBy('material_price.reserve');

        /** MaterialInfo */
        $dbal
            ->leftJoin(
                'material_event',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
            )
            ->addGroupBy('material_info.article')
            ->addGroupBy('material_info.sort');

        /** Даты сырья */
        $dbal
            ->addSelect('material_active.active_from')
            ->addSelect('material_active.active_to')
            ->join(
                'material',
                MaterialActive::class,
                'material_active',
                'material_active.event = material.event'
            );

        /** OFFERS */
        $method = 'leftJoin';

        if($this->filter?->getOffer())
        {
            $method = 'join';
            $dbal->setParameter('offer', $this->filter->getOffer());
        }

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.value as material_offer_value')
            ->{$method}(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event '.($this->filter?->getOffer() ? ' AND material_offer.value = :offer' : '').' '
            );

        /** Цена торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferPrice::class,
            'material_offer_price',
            'material_offer_price.offer = material_offer.id'
        )
            ->addGroupBy('material_offer_price.price')
            ->addGroupBy('material_offer_price.currency');

        /** Наличие торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        )
            ->addGroupBy('material_offer_quantity.quantity')
            ->addGroupBy('material_offer_quantity.reserve');

        /** Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference as material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        /** VARIATIONS */
        $method = 'leftJoin';

        if($this->filter?->getVariation())
        {
            $method = 'join';
            $dbal->setParameter('variation', $this->filter->getVariation());
        }

        $dbal
            ->addSelect('material_offer_variation.id as material_variation_uid')
            ->addSelect('material_offer_variation.value as material_variation_value')
            ->{$method}(
                'material_offer',
                MaterialVariation::class,
                'material_offer_variation',
                'material_offer_variation.offer = material_offer.id '.($this->filter?->getVariation() ? ' AND material_offer_variation.value = :variation' : '').' '
            );


        /** Цена множественного варианта */
        $dbal->leftJoin(
            'category_offer_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_offer_variation.id'
        )
            ->addGroupBy('material_variation_price.price')
            ->addGroupBy('material_variation_price.currency');

        /** Наличие множественного варианта */
        $dbal->leftJoin(
            'category_offer_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_offer_variation.id'
        )
            ->addGroupBy('material_variation_quantity.quantity')
            ->addGroupBy('material_variation_quantity.reserve');

        /** Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_offer_variation',
                CategoryMaterialVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = material_offer_variation.category_variation'
            );

        /** MODIFICATION */
        $method = 'leftJoin';

        if($this->filter?->getModification())
        {
            $method = 'join';
            $dbal->setParameter('modification', $this->filter->getModification());
        }

        $dbal
            ->addSelect('material_offer_modification.id as material_modification_uid')
            ->addSelect('material_offer_modification.value as material_modification_value')
            ->{$method}(
                'category_offer_variation',
                MaterialModification::class,
                'material_offer_modification',
                'material_offer_modification.variation = material_offer_variation.id '.($this->filter?->getModification() ? ' AND material_offer_modification.value = :modification' : '').' '
            );

        /** Цена множественного варианта */
        $dbal->leftJoin(
            'material_offer_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_offer_modification.id'
        )
            ->addGroupBy('material_modification_price.price')
            ->addGroupBy('material_modification_price.currency');

        /** Наличие множественного варианта */
        $dbal->leftJoin(
            'material_offer_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_offer_modification.id'
        )
            ->addGroupBy('material_modification_quantity.quantity')
            ->addGroupBy('material_modification_quantity.reserve');

        /** Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_offer_modification',
                CategoryMaterialModification::class,
                'category_offer_modification',
                'category_offer_modification.id = material_offer_modification.category_modification'
            );

        /** Артикул сырья */
        $dbal->addSelect("
			CASE
			   WHEN material_offer_modification.article IS NOT NULL 
			   THEN material_offer_modification.article
			   
			   WHEN material_offer_variation.article IS NOT NULL 
			   THEN material_offer_variation.article
			   
			   WHEN material_offer.article IS NOT NULL 
			   THEN material_offer.article
			   
			   WHEN material_info.article IS NOT NULL 
			   THEN material_info.article
			   
			   ELSE NULL
			END AS material_article
		"
        );

        /** Фото сырья */
        $dbal->leftJoin(
            'material_offer_modification',
            MaterialModificationImage::class,
            'material_offer_modification_image',
            '
			material_offer_modification_image.modification = material_offer_modification.id AND
			material_offer_modification_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_offer_variation_image',
            '
			material_offer_variation_image.variation = material_offer_variation.id AND
			material_offer_variation_image.root = true
			'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            '
			material_offer_variation_image.name IS NULL AND
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
			material_photo.event = material_event.id AND
			material_photo.root = true
			'
        );

        $dbal->addSelect("
			CASE
			
			 WHEN material_offer_modification_image.name IS NOT NULL 
			 THEN CONCAT ( '/upload/".$dbal->table(MaterialModificationImage::class)."', '/', material_offer_modification_image.name)
			 
			 WHEN material_offer_variation_image.name IS NOT NULL 
			 THEN CONCAT ( '/upload/".$dbal->table(MaterialVariationImage::class)."' , '/', material_offer_variation_image.name)
			   
			 WHEN material_offer_images.name IS NOT NULL 
			 THEN CONCAT ( '/upload/".$dbal->table(MaterialOfferImage::class)."' , '/', material_offer_images.name)
			 
			 WHEN material_photo.name IS NOT NULL 
			 THEN CONCAT ( '/upload/".$dbal->table(MaterialPhoto::class)."' , '/', material_photo.name)
			 
			 ELSE NULL
			 
			END AS material_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			
                WHEN material_offer_modification_image.name IS NOT NULL 
                THEN material_offer_modification_image.ext
			
			   WHEN material_offer_variation_image.name IS NOT NULL 
			   THEN material_offer_variation_image.ext
			   
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.ext
			   
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.ext
			   
			   ELSE NULL
			END AS material_image_ext
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN material_offer_variation_image.name IS NOT NULL 
			   THEN material_offer_variation_image.cdn
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.cdn
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.cdn
					
			   ELSE NULL
			END AS material_image_cdn
		"
        );

        /** Стоимость сырья */
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
        $dbal->addSelect("
			CASE
			   WHEN COALESCE(material_modification_price.price, 0) != 0 
			   THEN material_modification_price.currency
			   
			   WHEN COALESCE(material_variation_price.price, 0) != 0 
			   THEN material_variation_price.currency
			   
			   WHEN COALESCE(material_offer_price.price, 0) != 0 
			   THEN material_offer_price.currency
			   
			   WHEN COALESCE(material_price.price, 0) != 0 
			   THEN material_price.currency
			   
			   ELSE NULL
			END AS material_currency"
        );


        $dbal->leftJoin(
            'material_event_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_event_category.category'
        );

        $dbal
            ->addSelect('category_info.url AS category_url')
            ->leftJoin(
                'material_event_category',
                CategoryMaterialInfo::class,
                'category_info',
                'category_info.event = category.event'
            );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );


        /** Только с ценой */
        $dbal->andWhere("
 			CASE
			   WHEN material_modification_price.price  IS NOT NULL THEN material_modification_price.price
			   WHEN material_variation_price.price  IS NOT NULL THEN material_variation_price.price
			   WHEN material_offer_price.price IS NOT NULL THEN material_offer_price.price
			   WHEN material_price.price IS NOT NULL THEN material_price.price
			   ELSE 0
			END > 0
 		"
        );


        /** Только при наличии */
        $dbal->andWhere("
 			CASE
			   WHEN material_modification_quantity.quantity IS NOT NULL THEN (material_modification_quantity.quantity - material_modification_quantity.reserve)
			   WHEN material_variation_quantity.quantity IS NOT NULL THEN (material_variation_quantity.quantity - material_variation_quantity.reserve)
			   WHEN material_offer_quantity.quantity IS NOT NULL THEN (material_offer_quantity.quantity - material_offer_quantity.reserve)
			   WHEN material_price.quantity  IS NOT NULL THEN (material_price.quantity - material_price.reserve)
			   ELSE 0
			END > 0
			"
        );

        $dbal->addOrderBy('material_modification_quantity.reserve', 'DESC');
        $dbal->addOrderBy('material_variation_quantity.reserve', 'DESC');
        $dbal->addOrderBy('material_offer_quantity.reserve', 'DESC');
        $dbal->addOrderBy('material_price.reserve', 'DESC');

        $dbal->addOrderBy('material_info.sort', 'DESC');

        $dbal->allGroupByExclude();

        if(false !== $this->maxResult)
        {
            $dbal->setMaxResults($this->maxResult);
        }

        $dbal->enableCache('materials-catalog', 86400, false);

        $result = $dbal->fetchAllAssociative();

        return empty($result) ? false : $result;
    }

}