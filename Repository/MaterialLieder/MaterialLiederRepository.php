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

namespace BaksDev\Materials\Catalog\Repository\MaterialLieder;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Active\MaterialActive;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Info\CategoryMaterialInfo;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;

final class MaterialLiederRepository implements MaterialLiederInterface
{
    private CategoryMaterialUid|false $categoryUid = false;

    private int|false $maxResult = false;

    public function __construct(
        private readonly DBALQueryBuilder $dbal
    ) {}

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
     * Метод возвращает ограниченный по количеству элементов список лидеров продаж сырья, суммируя количество резервов на сырьё
     *
     * @return array{
     *     "material_name": string,
     *     "url": string,
     *     "sort": int,
     *     "active_from": string,
     *     "active_to": string,
     *     "category_url": string,
     * } | false
     */
    public function find(): array|false
    {
        $dbal = $this->dbal
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->from(Material::class, 'material');

        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );

        /** Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );

        /** MaterialInfo */
        $dbal
            ->addSelect('material_info.sort')
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
            );

        /** Даты категории */
        $dbal
            ->addSelect('material_active.active_from')
            ->addSelect('material_active.active_to')
            ->join(
                'material',
                MaterialActive::class,
                'material_active',
                'material_active.event = material.event'
            );

        /** Торговое предложение */
        $dbal->leftJoin(
            'material',
            MaterialOffer::class,
            'material_offer',
            'material_offer.event = material.event'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        /** Множественный вариант */
        $dbal->leftOneJoin(
            'material_offer',
            MaterialVariation::class,
            'material_offer_variation',
            'material_offer_variation.offer = material_offer.id'
        );

        $dbal->leftJoin(
            'material_offer_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_offer_variation.id'
        );

        /** Модификация множественного варианта */
        $dbal->leftJoin(
            'material_offer_variation',
            MaterialModification::class,
            'material_offer_modification',
            'material_offer_modification.variation = material_offer_variation.id'
        );

        $dbal->leftJoin(
            'material_offer_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_offer_modification.id'
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

        /** Только при наличии */
        $dbal->andWhere("
 			CASE
			   WHEN material_modification_quantity.quantity IS NOT NULL THEN (material_modification_quantity.quantity - material_modification_quantity.reserve)
			   WHEN material_variation_quantity.quantity IS NOT NULL THEN (material_variation_quantity.quantity - material_variation_quantity.reserve)
			   WHEN material_offer_quantity.quantity IS NOT NULL THEN (material_offer_quantity.quantity - material_offer_quantity.reserve)
			   WHEN material_price.quantity  IS NOT NULL THEN (material_price.quantity - material_price.reserve)
			   ELSE 0
			END > 0
 		");

        $dbal->addOrderBy('SUM(material_modification_quantity.reserve)', 'DESC');
        $dbal->addOrderBy('SUM(material_variation_quantity.reserve)', 'DESC');
        $dbal->addOrderBy('SUM(material_offer_quantity.reserve)', 'DESC');
        $dbal->addOrderBy('SUM(material_price.reserve)', 'DESC');

        $dbal->addOrderBy('material_info.sort', 'DESC');

        if(false !== $this->maxResult)
        {
            $dbal->setMaxResults($this->maxResult);
        }

        $dbal->allGroupByExclude();
        $dbal->enableCache('materials-catalog', 86400, false);

        $result = $dbal->fetchAllAssociative();

        return empty($result) ? false : $result;
    }
}