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

namespace BaksDev\Materials\Catalog\Repository\MaterialQuantity;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Products\Product\Type\Material\MaterialUid;

final class MaterialVariationQuantityRepository implements MaterialVariationQuantityInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /** Метод возвращает количественный учет множественного варианта */
    public function getMaterialVariationQuantity(
        MaterialUid $material,
        MaterialOfferConst $offer,
        MaterialVariationConst $variation
    ): ?MaterialsVariationQuantity
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(Material::class, 'material')
            ->where('material.id = :material')
            ->setParameter(
                key: 'material',
                value: $material,
                type: MaterialUid::TYPE
            );

        $qb->join(
            MaterialEvent::class,
            'event',
            'WITH',
            'event.id = material.event'
        );

        // Торговое предложение

        $qb
            ->join(
                MaterialOffer::class,
                'offer',
                'WITH',
                'offer.event = event.id AND offer.const = :offer_const'
            )
            ->setParameter(
                key: 'offer_const',
                value: $offer,
                type: MaterialOfferConst::TYPE
            );

        // Множественный вариант

        $qb
            ->join(
                MaterialVariation::class,
                'variation',
                'WITH',
                'variation.offer = offer.id AND variation.const = :variation_const'
            )
            ->setParameter(
                key: 'variation_const',
                value: $variation,
                type: MaterialVariationConst::TYPE
            );


        $qb
            ->select('quantity')
            ->leftJoin(
                MaterialsVariationQuantity::class,
                'quantity',
                'WITH',
                'quantity.variation = variation.id'
            );


        // Только если у модификации указан количественный учет

        $qb->join(
            CategoryMaterialVariation::class,
            'category_variation',
            'WITH',
            'category_variation.id = variation.categoryVariation AND category_variation.quantitative = true'
        );

        return $qb->getOneOrNullResult();
    }
}
