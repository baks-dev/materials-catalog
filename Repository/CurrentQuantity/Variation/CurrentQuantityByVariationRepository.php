<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Catalog\Repository\CurrentQuantity\Variation;

use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\ProductVariationQuantity;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use Doctrine\ORM\EntityManagerInterface;

final class CurrentQuantityByVariationRepository implements CurrentQuantityByVariationInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function getVariationQuantity(
        MaterialEventUid $event,
        ProductOfferUid $offer,
        ProductVariationUid $variation
    ): ?ProductVariationQuantity
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('quantity');

        $qb->from(MaterialEvent::class, 'event');


        $qb->join(
            Material::class,
            'product',
            'WITH',
            'product.id = event.main'
        );

        /** Торговое предложение */

        $qb->join(
            ProductOffer::class,
            'offer',
            'WITH',
            'offer.id = :offer AND offer.event = event.id'
        );
        $qb->setParameter('offer', $offer, ProductOfferUid::TYPE);

        $qb->leftJoin(
            ProductOffer::class,
            'current_offer',
            'WITH',
            'current_offer.const = offer.const AND current_offer.event = product.event'
        );


        /** Множественный вариант торгового предложения */

        $qb->join(
            ProductVariation::class,
            'variation',
            'WITH',
            'variation.id = :variation AND variation.offer = offer.id'
        );
        $qb->setParameter('variation', $variation, ProductVariationUid::TYPE);

        $qb->leftJoin(
            ProductVariation::class,
            'current_variation',
            'WITH',
            'current_variation.const = variation.const AND current_variation.offer = current_offer.id'
        );


        /** Текущее наличие */
        $qb->leftJoin(
            ProductVariationQuantity::class,
            'quantity',
            'WITH',
            'quantity.variation = current_variation.id'
        );


        $qb->where('event.id = :event');
        $qb->setParameter('event', $event, MaterialEventUid::TYPE);

        return $qb->getQuery()->getOneOrNullResult();
    }

}
