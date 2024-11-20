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

namespace BaksDev\Materials\Catalog\Repository\CurrentProductByArticle;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Info\ProductInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;

final readonly class ProductConstByArticleRepository implements ProductConstByArticleInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /** Метод возвращает активные идентификаторы продукции */
    public function find(string $article): CurrentProductDTO|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(Material::class, 'product');

        $dbal->leftJoin(
            'product',
            ProductInfo::class,
            'info',
            'info.product = product'
        );

        $dbal->leftJoin(
            'product',
            ProductOffer::class,
            'offer',
            'offer.event = product.event'
        );

        $dbal->leftJoin(
            'offer',
            ProductVariation::class,
            'variation',
            'variation.offer = offer.id'
        );

        $dbal->leftJoin(
            'variation',
            ProductModification::class,
            'modification',
            'modification.variation = variation.id'
        );

        $dbal->where('info.article = :article');
        $dbal->orWhere('offer.article = :article');
        $dbal->orWhere('variation.article = :article');
        $dbal->orWhere('modification.article = :article');

        $dbal->setParameter('article', $article);


        $dbal->select('product.id AS product');
        $dbal->addSelect('product.event');

        /** Торговое предложение */
        $dbal->addSelect('offer.id AS offer');
        $dbal->addSelect('offer.const AS offer_const');

        /** Множественный вариант торгового предложения */
        $dbal->addSelect('variation.id AS variation');
        $dbal->addSelect('variation.const AS variation_const');

        /** Модификация множественного варианта торгового предложения */
        $dbal->addSelect('modification.id AS modification');
        $dbal->addSelect('modification.const AS modification_const');

        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchHydrate(CurrentProductDTO::class);
    }
}
