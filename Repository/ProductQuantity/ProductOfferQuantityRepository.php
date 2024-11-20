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

namespace BaksDev\Materials\Catalog\Repository\ProductQuantity;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\ProductOfferQuantity;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use Doctrine\ORM\EntityManagerInterface;

final class ProductOfferQuantityRepository implements ProductOfferQuantityInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /** Метод возвращает количественный учет торгового предложения */
    public function getProductOfferQuantity(
        MaterialUid $product,
        ProductOfferConst $offer
    ): ?ProductOfferQuantity
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(Material::class, 'product')
            ->where('product.id = :product')
            ->setParameter('product', $product, MaterialUid::TYPE);

        $qb->join(
            MaterialEvent::class,
            'event',
            'WITH',
            'event.id = product.event'
        );

        // Торговое предложение

        $qb->join(
            ProductOffer::class,
            'offer',
            'WITH',
            'offer.event = event.id AND offer.const = :offer_const'
        )
            ->setParameter(
                'offer_const',
                $offer,
                ProductOfferConst::TYPE
            );


        $qb
            ->select('quantity')
            ->leftJoin(
                ProductOfferQuantity::class,
                'quantity',
                'WITH',
                'quantity.offer = offer.id'
            );


        // Только если у оргового предложения указан количественный учет

        $qb->join(
            CategoryProductOffers::class,
            'category_offer',
            'WITH',
            'category_offer.id = offer.categoryOffer AND category_offer.quantitative = true'
        );

        return $qb->getOneOrNullResult();
    }
}
