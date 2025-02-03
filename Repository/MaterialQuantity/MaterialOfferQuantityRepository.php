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
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use Doctrine\ORM\EntityManagerInterface;

final class MaterialOfferQuantityRepository implements MaterialOfferQuantityInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /** Метод возвращает количественный учет торгового предложения */
    public function getMaterialOfferQuantity(
        MaterialUid $material,
        MaterialOfferConst $offer
    ): ?MaterialOfferQuantity
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

        $qb->join(
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


        $qb
            ->select('quantity')
            ->leftJoin(
                MaterialOfferQuantity::class,
                'quantity',
                'WITH',
                'quantity.offer = offer.id'
            );


        // Только если у оргового предложения указан количественный учет

        $qb->join(
            CategoryMaterialOffers::class,
            'category_offer',
            'WITH',
            'category_offer.id = offer.categoryOffer AND category_offer.quantitative = true'
        );

        return $qb->getOneOrNullResult();
    }
}
