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

namespace BaksDev\Materials\Catalog\Repository\ProductByArticle;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\ProductInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;

final class ProductEventByArticleRepository implements ProductEventByArticleInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод возвращает по артикулу событие продукта
     */
    public function findProductEventByArticle(string $article): MaterialEvent|false
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(ProductInfo::class, 'info')
            ->where('info.article = :article');

        $qb->join(
            Material::class,
            'product',
            'WITH',
            'product.id = info.product'
        )
            ->setParameter('article', $article);

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = product.event'
            );

        /** @var Material $Product */
        $ProductEvent = $qb->getQuery()->getOneOrNullResult();

        if($ProductEvent)
        {
            return $ProductEvent;
        }


        /**
         * Поиск по артикулу в торговом предложении
         */

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(ProductOffer::class, 'offer')
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

        $qb->join(Material::class, 'product', 'WITH', 'product.event = event.id');

        /** @var MaterialEvent $ProductEvent */
        $ProductEvent = $qb->getQuery()->getOneOrNullResult();

        if($ProductEvent)
        {
            return $ProductEvent;
        }


        /**
         * Поиск по артикулу в множественном варианте торгового предложения
         */

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(ProductVariation::class, 'variation')
            ->where('variation.article = :article')
            ->setParameter('article', $article);

        $qb->join(ProductOffer::class, 'offer', 'WITH', 'offer.id = variation.offer');

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = offer.event'
            );

        $qb->join(Material::class, 'product', 'WITH', 'product.event = event.id');

        /** @var MaterialEvent $ProductEvent */
        $ProductEvent = $qb->getQuery()->getOneOrNullResult();

        if($ProductEvent)
        {
            return $ProductEvent;
        }

        /**
         * Поиск по артикулу в множественном варианте торгового предложения
         */

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);


        $qb
            ->from(ProductModification::class, 'modification')
            ->where('modification.article = :article')
            ->setParameter('article', $article);

        $qb->join(ProductVariation::class, 'variation', 'WITH', 'variation.id = modification.variation');
        $qb->join(ProductOffer::class, 'offer', 'WITH', 'offer.id = variation.offer');

        $qb
            ->select('event')
            ->join(
                MaterialEvent::class,
                'event',
                'WITH',
                'event.id = offer.event'
            );

        $qb->join(Material::class, 'product', 'WITH', 'product.event = event.id');

        return $qb->getQuery()->getOneOrNullResult() ?: false;
    }
}
