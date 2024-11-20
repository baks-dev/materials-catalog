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

namespace BaksDev\Materials\Catalog\Repository\ProductVariationConst;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;

final class ProductVariationConstRepository implements ProductVariationConstInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает идентификатор константу множественного варианта торгового предложения
     */
    public function getConst(ProductVariationUid|string $variation): ProductVariationConst|false
    {
        if(is_string($variation))
        {
            $variation = new ProductVariationUid($variation);
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(ProductVariation::class, 'variation')
            ->where('variation.id = :variation')
            ->setParameter('variation', $variation, ProductVariationUid::TYPE);

        /** Свойства конструктора объекта гидрации */

        $dbal
            ->addSelect('variation.const AS value');

        return $dbal
            ->enableCache('materials-catalog', 3600)
            ->fetchHydrate(ProductVariationConst::class);
    }
}
