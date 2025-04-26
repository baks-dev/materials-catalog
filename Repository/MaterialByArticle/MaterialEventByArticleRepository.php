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

namespace BaksDev\Materials\Catalog\Repository\MaterialByArticle;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;

final class MaterialEventByArticleRepository implements MaterialEventByArticleInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает по артикулу активное событие сырья
     */
    public function findMaterialEventByArticle(string $article): MaterialEvent|false
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(MaterialInfo::class, 'info')
            ->where('info.article = :article');

        $qb->join(
            Material::class,
            'material',
            'WITH',
            'material.id = info.material'
        )
            ->setParameter(
                key: 'article',
                value: $article
            );

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = material.event'
            );

        /** @var Material $Material */
        $MaterialEvent = $qb->getQuery()->getOneOrNullResult();

        if($MaterialEvent)
        {
            return $MaterialEvent;
        }

        /**
         * Поиск по артикулу в торговом предложении
         */

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(MaterialOffer::class, 'offer')
            ->where('offer.article = :article')
            ->setParameter('article', $article);

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = offer.event'
            );

        $qb->join(Material::class, 'material', 'WITH', 'material.event = event.id');

        /** @var MaterialEvent $MaterialEvent */
        $MaterialEvent = $qb->getQuery()->getOneOrNullResult();

        if($MaterialEvent)
        {
            return $MaterialEvent;
        }


        /**
         * Поиск по артикулу в множественном варианте торгового предложения
         */

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(MaterialVariation::class, 'variation')
            ->where('variation.article = :article')
            ->setParameter(
                key: 'article',
                value: $article
            );

        $qb->join(MaterialOffer::class, 'offer', 'WITH', 'offer.id = variation.offer');

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = offer.event'
            );

        $qb->join(Material::class, 'material', 'WITH', 'material.event = event.id');

        /** @var MaterialEvent $MaterialEvent */
        $MaterialEvent = $qb->getQuery()->getOneOrNullResult();

        if($MaterialEvent)
        {
            return $MaterialEvent;
        }

        /**
         * Поиск по артикулу в множественном варианте торгового предложения
         */

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);


        $qb
            ->from(MaterialModification::class, 'modification')
            ->where('modification.article = :article')
            ->setParameter(
                key: 'article',
                value: $article
            );

        $qb->join(MaterialVariation::class, 'variation', 'WITH', 'variation.id = modification.variation');
        $qb->join(MaterialOffer::class, 'offer', 'WITH', 'offer.id = variation.offer');

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = offer.event'
            );

        $qb->join(Material::class, 'material', 'WITH', 'material.event = event.id');

        return $qb->getOneOrNullResult() ?: false;
    }
}
