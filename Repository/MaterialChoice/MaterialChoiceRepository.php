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

namespace BaksDev\Materials\Catalog\Repository\MaterialChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Core\Type\Locale\Locale;
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
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Products\Product\Repository\MaterialsChoice\MaterialsChoiceInterface;
use Generator;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MaterialChoiceRepository implements MaterialChoiceInterface
{
    public function __construct(
        private ORMQueryBuilder $ORMQueryBuilder,
        private DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    /**
     * Метод возвращает все идентификаторы сырья (MaterialUid) с названием указанной категории
     */
    public function findAll(CategoryMaterialUid|false $category = false): array|false
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->from(Material::class, 'material');

        $qb->join(
            'material',
            MaterialInfo::class,
            'info',
            'info.material = material.id'
        );

        if($category)
        {
            $qb
                ->join(
                    'material',
                    MaterialCategory::class,
                    'category',
                    'category.event = material.event AND category.category = :category'
                )
                ->setParameter(
                    'category',
                    $category,
                    CategoryMaterialUid::TYPE
                );
        }


        $qb->join(
            'material',
            MaterialTrans::class,
            'trans',
            'trans.event = material.event AND trans.local = :local'
        );


        $qb->addSelect('material.id AS value');
        $qb->addSelect('trans.name AS attr');
        $qb->addSelect('info.article AS option');

        $qb->orderBy('trans.name');

        /* Кешируем результат ORM */
        return $qb
            ->enableCache('materials-catalog', 86400)
            ->fetchAllAssociativeIndexed(MaterialUid::class);

    }


    /**
     * Метод возвращает активные идентификаторы событий (MaterialEventUid) сырья
     */
    public function fetchAllMaterialEvent(): ?array
    {
        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->from(Material::class, 'material');

        $qb->join(
            MaterialInfo::class,
            'info',
            'WITH',
            'info.material = material.id'
        );

        $qb->join(
            MaterialActive::class,
            'active',
            'WITH',
            '
            active.event = material.event AND
            active.active = true AND
            active.activeFrom < CURRENT_TIMESTAMP() AND
            (active.activeTo IS NULL OR active.activeTo > CURRENT_TIMESTAMP())
		'
        );

        $qb->join(
            MaterialTrans::class,
            'trans',
            'WITH',
            'trans.event = material.event AND trans.local = :local'
        );


        /* Кешируем результат ORM */
        return $qb->enableCache('materials-catalog', 86400)->getResult();

    }


    /**
     * Метод возвращает идентификаторы событий (MaterialEventUid) доступной для продажи сырья
     */
    public function fetchAllMaterialEventByExists(CategoryMaterialUid|false $category = false): Generator
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->from(Material::class, 'material');

        if($category)
        {
            $dbal->join(
                'material',
                MaterialCategory::class,
                'category',
                'category.event = material.event AND category.category = :category AND category.root = TRUE'
            )
                ->setParameter('category', $category, CategoryMaterialUid::TYPE);
        }


        $dbal->leftJoin(
            'material',
            MaterialTrans::class,
            'trans',
            'trans.event = material.event AND trans.local = :local'
        );

        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );

        $dbal->leftJoin(
            'material',
            MaterialOffer::class,
            'material_offer',
            'material_offer.event = material.event'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialVariation::class,
            'material_variation',
            'material_variation.offer = material_offer.id'
        );

        $dbal->leftJoin(
            'material_variation',
            MaterialModification::class,
            'material_modification',
            'material_modification.variation = material_variation.id'
        );

        /**
         * Quantity
         */

        $dbal
            ->leftJoin(
                'material_offer',
                MaterialOfferQuantity::class,
                'material_offer_quantity',
                'material_offer_quantity.offer = material_offer.id'
            );

        $dbal
            ->leftJoin(
                'material_variation',
                MaterialsVariationQuantity::class,
                'material_variation_quantity',
                'material_variation_quantity.variation = material_variation.id'
            );

        $dbal
            ->leftJoin(
                'material_modification',
                MaterialModificationQuantity::class,
                'material_modification_quantity',
                'material_modification_quantity.modification = material_modification.id'
            );


        $dbal->addSelect('material.event AS value');
        $dbal->addSelect('trans.name AS attr');


        $dbal->addSelect('

            CASE
               WHEN SUM(material_modification_quantity.quantity - material_modification_quantity.reserve) > 0
               THEN SUM(material_modification_quantity.quantity - material_modification_quantity.reserve)

               WHEN SUM(material_variation_quantity.quantity - material_variation_quantity.reserve) > 0
               THEN SUM(material_variation_quantity.quantity - material_variation_quantity.reserve)

               WHEN SUM(material_offer_quantity.quantity - material_offer_quantity.reserve) > 0
               THEN SUM(material_offer_quantity.quantity - material_offer_quantity.reserve)

               WHEN SUM(material_price.quantity - material_price.reserve) > 0
               THEN SUM(material_price.quantity - material_price.reserve)

               ELSE 0
            END

        AS option');


        $dbal->andWhere('
            material_modification_quantity.quantity > 0 OR 
            material_variation_quantity.quantity > 0 OR 
            material_offer_quantity.quantity > 0 OR
            material_price.quantity > 0 
        ');


        $dbal->allGroupByExclude();

        return $dbal->enableCache('materials-catalog', 60)->fetchAllHydrate(MaterialEventUid::class);

    }

}
