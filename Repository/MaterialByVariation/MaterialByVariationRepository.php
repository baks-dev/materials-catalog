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

namespace BaksDev\Materials\Catalog\Repository\MaterialByVariation;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use Doctrine\ORM\EntityManagerInterface;

final class MaterialByVariationRepository implements MaterialByVariationInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает массив идентификаторов сырья
     */
    public function getMaterialByVariationOrNull(MaterialVariationUid|MaterialVariationConst $variation): ?array
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        if($variation instanceof MaterialVariationConst)
        {
            $qb
                ->addSelect('variation.id AS variation_id')
                ->from(MaterialVariation::class, 'variation')
                ->where('variation.const = :const')
                ->setParameter('const', $variation, MaterialVariationConst::TYPE);
        }

        if($variation instanceof MaterialVariationUid)
        {
            $qb
                ->from(MaterialVariation::class, 'var')
                ->where('var.id = :variation')
                ->setParameter('variation', $variation, MaterialVariationUid::TYPE);

            $qb
                ->join(
                    MaterialVariation::class,
                    'variation',
                    'WITH',
                    'variation.const = var.const'
                );
        }

        $qb
            ->addSelect('offer.id AS offer_id')
            ->join(
                MaterialOffer::class,
                'offer',
                'WITH',
                'offer.id = variation.offer'
            );

        $qb
            ->addSelect('event.id AS event_id')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = offer.event'
            );

        $qb
            ->addSelect('material.id AS material_id')
            ->join(
                Material::class,
                'material',
                'WITH',
                'material.event = event.id'
            );


        return $qb->getQuery()->getOneOrNullResult();
    }
}
