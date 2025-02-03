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

namespace BaksDev\Materials\Catalog\Repository\CurrentQuantity;

use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use Doctrine\ORM\EntityManagerInterface;

final class CurrentMaterialQuantityByEventRepository implements CurrentMaterialQuantityByEventInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}


    public function getQuantity(MaterialEventUid $event): ?MaterialPrice
    {
        $qb = $this->entityManager->createQueryBuilder();

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

        /** Текущее наличие */
        $qb
            ->select('quantity')
            ->leftJoin(
                MaterialPrice::class,
                'quantity',
                'WITH',
                'quantity.event = material.event'
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

}
