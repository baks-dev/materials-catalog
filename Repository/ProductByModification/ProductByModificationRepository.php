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

namespace BaksDev\Materials\Catalog\Repository\ProductByModification;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use InvalidArgumentException;

/**
 * Класс возвращает идентификаторы продукции по модификации
 */
final class ProductByModificationRepository implements ProductByModificationInterface
{
    private ?array $data = null;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Класс возвращает идентификаторы продукции по модификации
     */

    public function findModification(ProductModificationUid|ProductModificationConst $modification): self
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);


        if($modification instanceof ProductModificationConst)
        {
            $dbal
                ->addSelect('modification.id AS modification_id')
                ->addSelect('modification.const AS modification_const')
                ->from(ProductModification::class, 'modification')
                ->where('modification.const = :const')
                ->setParameter('const', $modification, ProductModificationConst::TYPE);
        }

        if($modification instanceof ProductModificationUid)
        {

            $dbal
                ->from(ProductModification::class, 'mod')
                ->where('mod.id = :modification')
                ->setParameter('modification', $modification, ProductModificationUid::TYPE);

            $dbal
                ->addSelect('modification.id AS modification_id')
                ->addSelect('modification.const AS modification_const')
                ->join(
                    'mod',
                    ProductModification::class,
                    'modification',
                    'modification.const = mod.const'
                );
        }

        $dbal
            ->addSelect('variation.id AS variation_id')
            ->addSelect('variation.const AS variation_const')
            ->join(
                'modification',
                ProductVariation::class,
                'variation',
                'variation.id = modification.variation'
            );

        $dbal
            ->addSelect('offer.id AS offer_id')
            ->addSelect('offer.const AS offer_const')
            ->join(
                'variation',
                ProductOffer::class,
                'offer',
                'offer.id = variation.offer'
            );

        $dbal
            ->join(
                'offer',
                MaterialEvent::class,
                'event',
                'event.id = offer.event'
            );

        $dbal
            ->addSelect('product.id AS id')
            ->addSelect('product.event AS event_id')
            ->join(
                'event',
                Material::class,
                'product',
                'product.event = event.id'
            );

        $result = $dbal
            ->enableCache('materials-catalog', 30)
            ->fetchAllAssociative();

        $this->data = count($result) === 1 ? current($result) : throw new InvalidArgumentException('Many Result');

        return $this;
    }

    public function getProduct(): ?MaterialUid
    {
        return isset($this->data['id']) ? new MaterialUid($this->data['id']) : null;
    }

    public function getEvent(): ?MaterialEventUid
    {
        return isset($this->data['event_id']) ? new MaterialEventUid($this->data['event_id']) : null;
    }


    public function getOffer(): ?ProductOfferUid
    {
        return isset($this->data['offer_id']) ? new ProductOfferUid($this->data['offer_id']) : null;
    }

    public function getOfferConst(): ?ProductOfferConst
    {
        return isset($this->data['offer_const']) ? new ProductOfferConst($this->data['offer_const']) : null;
    }


    public function getVariation(): ?ProductVariationUid
    {
        return isset($this->data['variation_id']) ? new ProductVariationUid($this->data['variation_id']) : null;
    }

    public function getVariationConst(): ?ProductVariationConst
    {
        return isset($this->data['variation_const']) ? new ProductVariationConst($this->data['variation_const']) : null;
    }


    public function getModification(): ?ProductModificationUid
    {
        return isset($this->data['modification_id']) ? new ProductModificationUid($this->data['modification_id']) : null;
    }

    public function getModificationConst(): ?ProductModificationConst
    {
        return isset($this->data['modification_const']) ? new ProductModificationConst($this->data['modification_const']) : null;
    }
}
