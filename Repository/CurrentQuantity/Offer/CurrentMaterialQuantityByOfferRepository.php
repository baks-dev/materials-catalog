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

namespace BaksDev\Materials\Catalog\Repository\CurrentQuantity\Offer;

use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use Doctrine\ORM\EntityManagerInterface;

final class CurrentMaterialQuantityByOfferRepository implements CurrentMaterialQuantityByOfferInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function getOfferQuantity(
        MaterialEventUid $event,
        MaterialOfferUid $offer
    ): ?MaterialOfferQuantity
    {

        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('quantity');

        $qb
            ->from(MaterialEvent::class, 'event')
            ->where('event.id = :event')
            ->setParameter('event', $event, MaterialEventUid::TYPE);

        $qb->join(
            Material::class,
            'material',
            'WITH',
            'material.id = event.main'
        );

        /** Торговое предложение */

        $qb->join(
            MaterialOffer::class,
            'offer',
            'WITH',
            'offer.id = :offer AND offer.event = event.id'
        )
            ->setParameter(
                'offer',
                $offer,
                MaterialOfferUid::TYPE
            );

        $qb->leftJoin(
            MaterialOffer::class,
            'current_offer',
            'WITH',
            'current_offer.const = offer.const AND current_offer.event = material.event'
        );


        /** Текущее наличие */
        $qb->leftJoin(
            MaterialOfferQuantity::class,
            'quantity',
            'WITH',
            'quantity.offer = current_offer.id'
        );


        return $qb->getQuery()->getOneOrNullResult();
    }

}
