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

namespace BaksDev\Materials\Catalog\Repository\MaterialDetailOffer;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Price\MaterialOfferPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Price\MaterialModificationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\MaterialVariationPrice;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Trans\CategoryMaterialOffersTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\Trans\CategoryMaterialModificationTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\Trans\CategoryMaterialVariationTrans;
use BaksDev\Products\Product\Type\Material\MaterialUid;

final class MaterialDetailOfferRepository implements MaterialDetailOfferInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(DBALQueryBuilder $DBALQueryBuilder)
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /** Метод возвращает торговые предложения сырья */
    public function fetchMaterialOfferAssociative(
        MaterialUid|string $material,
    ): array|bool
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('material.id')
            ->from(Material::class, 'material')
            ->where('material.id = :material')
            ->setParameter('material', $material, MaterialUid::TYPE);


        /* Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );

        /* Торговое предложение */

        $dbal
            ->addSelect('material_offer.value as material_offer_value')
            ->addSelect('material_offer.category_offer as category_offer')
            ->leftJoin(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event'
            );

        /* Цена торгового предложения */
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

        /* Множественные варианты торгового предложения */

        $dbal
            ->addSelect('material_offer_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_offer_variation',
                'material_offer_variation.offer = material_offer.id'
            );

        /* Цена множественного варианта */
        $dbal->leftJoin(
            'category_offer_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_offer_variation.id'
        );

        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_offer_variation',
                CategoryMaterialVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = material_offer_variation.category_variation'
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

        /* Модификация множественного варианта торгового предложения */

        $dbal
            ->addSelect('material_offer_modification.value as material_modification_value')
            ->leftJoin(
                'material_offer_variation',
                MaterialModification::class,
                'material_offer_modification',
                'material_offer_modification.variation = material_offer_variation.id'
            );

        /* Цена Модификации множественного варианта */
        $dbal->leftJoin(
            'material_offer_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_offer_modification.id'
        );

        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_offer_modification',
                CategoryMaterialModification::class,
                'category_offer_modification',
                'category_offer_modification.id = material_offer_modification.category_modification'
            );

        /* Получаем название типа */
        $dbal
            ->addSelect('category_offer_modification_trans.name as material_modification_name')
            ->leftJoin(
                'category_offer_modification',
                CategoryMaterialModificationTrans::class,
                'category_offer_modification_trans',
                'category_offer_modification_trans.modification = category_offer_modification.id AND category_offer_modification_trans.local = :local'
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
			   WHEN material_modification_price.price IS NOT NULL 
			   THEN material_modification_price.currency
			   
			   WHEN material_variation_price.price IS NOT NULL 
			   THEN material_variation_price.currency
			   
			   WHEN material_offer_price.price IS NOT NULL 
			   THEN material_offer_price.currency
			   
			   WHEN material_price.price IS NOT NULL 
			   THEN material_price.currency
			   
			   ELSE NULL
			END AS material_currency
		'
        );

        return $dbal->enableCache('materials-catalog', 86400)->fetchAllAssociative();
    }
}
