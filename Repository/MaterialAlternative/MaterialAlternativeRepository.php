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

namespace BaksDev\Materials\Catalog\Repository\MaterialAlternative;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Active\MaterialActive;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Price\MaterialOfferPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Price\MaterialModificationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\MaterialVariationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Entity\Property\MaterialProperty;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
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
use BaksDev\Materials\Category\Type\Section\Field\Id\CategoryMaterialSectionFieldUid;
use stdClass;

final class MaterialAlternativeRepository implements MaterialAlternativeInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function fetchAllAlternativeAssociative(
        string $offer,
        ?string $variation,
        ?string $modification,
        ?array $property = null
    ): ?array
    {

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        // ТОРГОВОЕ ПРЕДЛОЖЕНИЕ

        $dbal
            ->addSelect('material_offer.value as material_offer_value')
            ->addSelect('material_offer.id as material_offer_uid')
            ->from(MaterialOffer::class, 'material_offer');


        $dbal
            ->addSelect('material.id')
            ->addSelect('material.event')
            ->join(
                'material_offer',
                Material::class,
                'material',
                'material.event = material_offer.event'
            );


        // МНОЖЕСТВЕННЫЕ ВАРИАНТЫ

        $variationMethod = empty($variation) ? 'leftJoin' : 'join';

        $dbal
            ->addSelect('material_variation.value as material_variation_value')
            ->addSelect('material_variation.id as material_variation_uid')
            ->{$variationMethod}(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id '.(empty($variation) ? '' : 'AND material_variation.value = :variation')
            );

        if(!empty($variation))
        {
            $dbal->setParameter('variation', $variation);
        }

        $modificationMethod = empty($modification) ? 'leftJoin' : 'join';

        // МОДИФИКАЦИЯ
        $dbal
            ->addSelect('material_modification.value as material_modification_value')
            ->addSelect('material_modification.id as material_modification_uid')
            ->{$modificationMethod}(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id '.(empty($modification) ? '' : 'AND material_modification.value = :modification')
            )
            ->addGroupBy('material_modification.article');

        if(!empty($modification))
        {
            $dbal->setParameter('modification', $modification);
        }


        // Проверяем активность сырья
        $dbal
            ->addSelect('material_active.active_from')
            ->join(
                'material',
                MaterialActive::class,
                'material_active',
                'material_active.event = material.event AND material_active.active = true AND material_active.active_from < NOW()
			
			AND (
				CASE
				   WHEN material_active.active_to IS NOT NULL 
				   THEN material_active.active_to > NOW()
				   ELSE TRUE
				END
			)
		'
            );


        // Название твоара
        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );


        $dbal
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id '
            );

        // Артикул сырья

        $dbal->addSelect('
            COALESCE(
                material_modification.article, 
                material_variation.article, 
                material_offer.article, 
                material_info.article
            ) AS article
		');


        /**
         * ТИПЫ ТОРГОВЫХ ПРЕДЛОЖЕНИЙ
         */

        // Получаем тип торгового предложения
        $dbal
            ->addSelect('category_offer.reference AS material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        // Получаем название торгового предложения
        $dbal
            ->addSelect('category_offer_trans.name as material_offer_name')
            ->leftJoin(
                'category_offer',
                CategoryMaterialOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );


        // Получаем тип множественного варианта
        $dbal
            ->addSelect('category_offer_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = material_variation.category_variation'
            );

        // Получаем название множественного варианта
        $dbal
            ->addSelect('category_offer_variation_trans.name as material_variation_name')
            ->leftJoin(
                'category_offer_variation',
                CategoryMaterialVariationTrans::class,
                'category_offer_variation_trans',
                'category_offer_variation_trans.variation = category_offer_variation.id AND category_offer_variation_trans.local = :local'
            );

        // Получаем тип модификации множественного варианта
        $dbal
            ->addSelect('category_offer_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_offer_modification',
                'category_offer_modification.id = material_modification.category_modification'
            );

        // Получаем название типа модификации
        $dbal
            ->addSelect('category_offer_modification_trans.name as material_modification_name')
            ->leftJoin(
                'category_offer_modification',
                CategoryMaterialModificationTrans::class,
                'category_offer_modification_trans',
                'category_offer_modification_trans.modification = category_offer_modification.id AND category_offer_modification_trans.local = :local'
            );


        /**
         * СТОИМОСТЬ И ВАЛЮТА сырья
         */

        $dbal->addSelect(
            '
			CASE
			   WHEN material_modification_price.price IS NOT NULL AND material_modification_price.price > 0 
			   THEN material_modification_price.price
			   
			   WHEN material_variation_price.price IS NOT NULL AND material_variation_price.price > 0 
			   THEN material_variation_price.price
			   
			   WHEN material_offer_price.price IS NOT NULL AND material_offer_price.price > 0 
			   THEN material_offer_price.price
			   
			   WHEN material_price.price IS NOT NULL AND material_price.price > 0 
			   THEN material_price.price
			   
			   ELSE NULL
			END AS price
		'
        );


        // Валюта сырья

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
			END AS currency
		'
        );

        // Базовая Цена товара
        $dbal
            ->leftJoin(
                'material',
                MaterialPrice::class,
                'material_price',
                'material_price.event = material.event'
            )
            ->addGroupBy('material_price.currency')
            ->addGroupBy('material_price.reserve');

        $dbal
            ->leftJoin(
                'material_offer',
                MaterialOfferPrice::class,
                'material_offer_price',
                'material_offer_price.offer = material_offer.id'
            )
            ->addGroupBy('material_offer_price.currency');

        // Цена множественного варианта
        $dbal
            ->leftJoin(
                'material_variation',
                MaterialVariationPrice::class,
                'material_variation_price',
                'material_variation_price.variation = material_variation.id'
            )
            ->addGroupBy('material_variation_price.currency');


        // Цена модификации множественного варианта
        $dbal
            ->leftJoin(
                'material_modification',
                MaterialModificationPrice::class,
                'material_modification_price',
                'material_modification_price.modification = material_modification.id'
            )
            ->addGroupBy('material_modification_price.currency');


        /**
         * НАЛИЧИЕ сырья
         */

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
			   
			END AS quantity
		'
        );

        // Наличие и резерв торгового предложения

        $dbal
            ->leftJoin(
                'material_offer',
                MaterialOfferQuantity::class,
                'material_offer_quantity',
                'material_offer_quantity.offer = material_offer.id'
            )
            ->addGroupBy('material_offer_quantity.reserve');

        // Наличие и резерв множественного варианта
        $dbal
            ->leftJoin(
                'category_offer_variation',
                MaterialsVariationQuantity::class,
                'material_variation_quantity',
                'material_variation_quantity.variation = material_variation.id'
            )
            ->addGroupBy('material_variation_quantity.reserve');

        // Наличие и резерв модификации множественного варианта
        $dbal
            ->leftJoin(
                'category_offer_modification',
                MaterialModificationQuantity::class,
                'material_modification_quantity',
                'material_modification_quantity.modification = material_modification.id'
            )
            ->addGroupBy('material_modification_quantity.reserve');


        /**
         * КАТЕГОРИЯ
         */

        $dbal->leftJoin(
            'material',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material.event AND material_event_category.root = true'
        );


        $dbal->leftJoin(
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
            ->join(
                'category',
                CategoryMaterialInfo::class,
                'category_info',
                'category_info.event = category.event AND category_info.active = true'
            );

        $dbal->leftJoin(
            'category',
            CategoryMaterialSection::class,
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

                $dbal->join(
                    'material_offer',
                    MaterialProperty::class,
                    'material_property_'.$alias,
                    'material_property_'.$alias.'.event = material_offer.event AND material_property_'.$alias.'.value = :props_'.$alias
                );

                $dbal->setParameter('props_'.$alias, $props->field_value);
            }
        }

        /**
         * СВОЙСТВА, УЧАСТВУЮЩИЕ В ПРЕВЬЮ
         */

        $dbal->leftJoin(
            'category_section',
            CategoryMaterialSectionField::class,
            'category_section_field',
            'category_section_field.section = category_section.id AND category_section_field.card = TRUE'
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
            'category_material_property',
            'category_material_property.event = material.event AND category_material_property.field = category_section_field.const'
        );

        $dbal->addSelect(
            "JSON_AGG
		( DISTINCT
			
				JSONB_BUILD_OBJECT
				(
				
					'0', category_section_field.sort,
					'field_type', category_section_field.type,
					'field_trans', category_section_field_trans.name,
					'field_value', category_material_property.value
				)
			
		)
			AS category_section_field"
        );

        $dbal->where('material_offer.value = :offer');
        $dbal->setParameter('offer', $offer);
        $dbal->setMaxResults(1000);

        $dbal->allGroupByExclude();

        $dbal->orderBy('quantity', 'DESC');

        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAllAssociative();
    }
}
