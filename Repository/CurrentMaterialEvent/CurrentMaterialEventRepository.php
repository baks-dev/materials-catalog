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

namespace BaksDev\Materials\Catalog\Repository\CurrentMaterialEvent;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;

final readonly class CurrentMaterialEventRepository implements CurrentMaterialEventInterface
{
    public function __construct(private ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает активное событие сырья
     */
    public function findByMaterial(Material|MaterialUid|string $material): ?MaterialEvent
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        if($material instanceof Material)
        {
            $material = $material->getId();
        }

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(Material::class, 'material')
            ->where('material.id = :material')
            ->setParameter('material', $material, MaterialUid::TYPE);
        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = material.event AND event.main = material.id'
            );


        return $qb->getOneOrNullResult();
    }

    /**
     * Метод возвращает активное событие сырья по идентификатору события
     */
    public function findByEvent(MaterialEvent|MaterialEventUid|string $last): ?MaterialEvent
    {
        if(is_string($last))
        {
            $last = new MaterialEventUid($last);
        }

        if($last instanceof MaterialEvent)
        {
            $last = $last->getId();
        }

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(MaterialEvent::class, 'last')
            ->where('last.id = :last')
            ->setParameter('last', $last, MaterialEventUid::TYPE);

        $qb
            ->join(
                Material::class,
                'material',
                'WITH',
                'material.id = last.main'
            );

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = material.event'
            );


        return $qb->getOneOrNullResult();
    }

}
