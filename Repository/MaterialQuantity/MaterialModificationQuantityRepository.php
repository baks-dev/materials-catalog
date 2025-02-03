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
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;

final class MaterialModificationQuantityRepository implements MaterialModificationQuantityInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /** Метод возвращает количественный учет модификации множественного варианта */
    public function getMaterialModificationQuantity(
        MaterialUid $material,
        MaterialOfferConst $offer,
        MaterialVariationConst $variation,
        MaterialModificationConst $modification
    ): ?MaterialModificationQuantity
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);


        $qb
            ->from(Material::class, 'material')
            ->where('material.id = :material')
            ->setParameter('material', $material, MaterialUid::TYPE);

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
                'offer_const',
                $offer,
                MaterialOfferConst::TYPE
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
                'variation_const',
                $variation,
                MaterialVariationConst::TYPE
            );

        // Модификация множественного варианта

        $qb
            ->join(
                MaterialModification::class,
                'modification',
                'WITH',
                'modification.variation = variation.id AND modification.const = :modification_const'
            )
            ->setParameter(
                'modification_const',
                $modification,
                MaterialModificationConst::TYPE
            );

        $qb
            ->select('quantity')
            ->leftJoin(
                MaterialModificationQuantity::class,
                'quantity',
                'WITH',
                'quantity.modification = modification.id'
            );


        // Только если у модификации указан количественный учет

        $qb->join(
            CategoryMaterialModification::class,
            'category_modification',
            'WITH',
            'category_modification.id = modification.categoryModification AND category_modification.quantitative = true'
        );

        return $qb->getOneOrNullResult();
    }
}
