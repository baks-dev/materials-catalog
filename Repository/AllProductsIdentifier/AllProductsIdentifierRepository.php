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

namespace BaksDev\Materials\Catalog\Repository\AllProductsIdentifier;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Generator;

final class AllProductsIdentifierRepository implements AllProductsIdentifierInterface
{
    private MaterialUid|false $product = false;

    private ProductOfferConst|false $offerConst = false;

    private ProductVariationConst|false $offerVariation = false;

    private ProductModificationConst|false $offerModification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forProduct(Material|MaterialUid|string $product): self
    {
        if(is_string($product))
        {
            $product = new MaterialUid($product);
        }

        if($product instanceof Material)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    public function forOfferConst(ProductOfferConst|string $offerConst): self
    {
        if(is_string($offerConst))
        {
            $offerConst = new ProductOfferConst($offerConst);
        }

        $this->offerConst = $offerConst;

        return $this;
    }

    public function forVariationConst(ProductVariationConst|string $offerVariation): self
    {
        if(is_string($offerVariation))
        {
            $offerVariation = new ProductVariationConst($offerVariation);
        }

        $this->offerVariation = $offerVariation;

        return $this;
    }

    public function forModificationConst(ProductModificationConst|string $offerModification): self
    {
        if(is_string($offerModification))
        {
            $offerModification = new ProductModificationConst($offerModification);
        }

        $this->offerModification = $offerModification;

        return $this;
    }


    /**
     * Метод возвращает все идентификаторы продукции с её торговыми предложениями
     */
    public function findAll(): Generator|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('product.id AS product_id')
            ->addSelect('product.event AS product_event')
            ->from(Material::class, 'product');

        if($this->product)
        {
            $dbal
                ->where('product.id = :product')
                ->setParameter(
                    'product',
                    $this->product,
                    MaterialUid::TYPE
                );
        }


        $dbal
            ->addSelect('offer.id AS offer_id')
            ->addSelect('offer.const AS offer_const');

        if($this->offerConst)
        {
            $dbal->join(
                'product',
                ProductOffer::class,
                'offer',
                'offer.event = product.event AND offer.const = :offer_const'
            )
                ->setParameter(
                    'offer_const',
                    $this->offerConst,
                    ProductOfferConst::TYPE
                );
        }
        else
        {
            $dbal->leftJoin(
                'product',
                ProductOffer::class,
                'offer',
                'offer.event = product.event'
            );
        }


        $dbal
            ->addSelect('variation.id AS variation_id')
            ->addSelect('variation.const AS variation_const');

        if($this->offerVariation)
        {
            $dbal->join(
                'offer',
                ProductVariation::class,
                'variation',
                'variation.offer = offer.id AND variation.const = :variation_const'
            )
                ->setParameter(
                    'variation_const',
                    $this->offerVariation,
                    ProductVariationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'offer',
                    ProductVariation::class,
                    'variation',
                    'variation.offer = offer.id'
                );
        }

        $dbal
            ->addSelect('modification.id AS modification_id')
            ->addSelect('modification.const AS modification_const');

        if($this->offerModification)
        {
            $dbal
                ->join(
                    'variation',
                    ProductModification::class,
                    'modification',
                    'modification.variation = variation.id AND modification.const = :modification_const'
                )
                ->setParameter(
                    'modification_const',
                    $this->offerModification,
                    ProductModificationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'variation',
                    ProductModification::class,
                    'modification',
                    'modification.variation = variation.id'
                );
        }

        return $dbal->fetchAllGenerator();
    }
}
