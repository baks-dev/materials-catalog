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

namespace BaksDev\Materials\Catalog\Repository\MaterialQuantity\MaterialQuantityByArticle;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;

final class MaterialQuantityByArticleRepository implements MaterialQuantityByArticleInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод получает активные остатки сырья по артикулу
     */
    public function find(string $article): int|false
    {

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->from(Material::class, 'material');

        $dbal
            ->leftJoin(
                'material',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
            );


        $dbal
            ->leftJoin(
                'material',
                MaterialPrice::class,
                'material_price',
                'material_price.event = material.event'
            );


        /** Торговое предложение */

        $dbal
            ->leftJoin(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event'
            );


        /** Множественные варианты торгового предложения */
        $dbal
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id'
            );


        /** Модификация множественного варианта */
        $dbal
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id'
            );


        /* Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        /* Наличие и резерв множественного варианта */
        $dbal->leftJoin(
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


        $dbal->addSelect('
        COALESCE(
            (material_modification_quantity.quantity - material_modification_quantity.reserve),
            (material_variation_quantity.quantity - material_variation_quantity.reserve),
            (material_offer_quantity.quantity - material_offer_quantity.reserve),
            (material_price.quantity - material_price.reserve),
            0
        ) AS material_quantity');


        $dbal->where('material_modification.article = :article');
        $dbal->orWhere('material_variation.article = :article');
        $dbal->orWhere('material_offer.article = :article');
        $dbal->orWhere('material_info.article = :article');

        $dbal->setParameter('article', $article);

        $result = $dbal->fetchOne();

        return $result !== false ? (int) $result : false;

    }
}
