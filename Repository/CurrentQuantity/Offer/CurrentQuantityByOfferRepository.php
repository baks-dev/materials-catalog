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

namespace BaksDev\Materials\Catalog\Repository\CurrentQuantity\Offer;

use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use Doctrine\ORM\EntityManagerInterface;

final class CurrentQuantityByOfferRepository implements CurrentQuantityByOfferInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function getOfferQuantity(
        MaterialEventUid $event,
        ProductOfferUid $offer
    ): ?ProductOfferQuantity
    {

        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('quantity');

        $qb
            ->from(MaterialEvent::class, 'event')
            ->where('event.id = :event')
            ->setParameter('event', $event, MaterialEventUid::TYPE);

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
        )
            ->setParameter(
                'offer',
                $offer,
                ProductOfferUid::TYPE
            );

        $qb->leftJoin(
            ProductOffer::class,
            'current_offer',
            'WITH',
            'current_offer.const = offer.const AND current_offer.event = product.event'
        );


        /** Текущее наличие */
        $qb->leftJoin(
            ProductOfferQuantity::class,
            'quantity',
            'WITH',
            'quantity.offer = current_offer.id'
        );


        return $qb->getQuery()->getOneOrNullResult();
    }

}
