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

use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\ProductModificationUid;

final class CurrentProductDTO
{
    /** ID продукта */
    private MaterialUid $product;

    /** ID события продукта */
    private MaterialEventUid $event;

    /** Торговое предложение */
    private ?ProductOfferUid $offer = null;
    private ?ProductOfferConst $offerConst = null;

    /** Множественный вариант торгового предложения */
    private ?ProductVariationUid $variation = null;
    private ?ProductVariationConst $variationConst = null;

    /** Модификация множественного варианта торгового предложения */
    private ?ProductModificationUid $modification = null;
    private ?ProductModificationConst $modificationConst = null;

    public function __construct(
        string $product,
        string $event,

        /** Торговое предложение */
        ?string $offer,
        ?string $offer_const,

        /** Множественный вариант торгового предложения */
        ?string $variation,
        ?string $variation_const,

        /** Модификация множественного варианта торгового предложения */
        ?string $modification,
        ?string $modification_const,
    )
    {

        $this->product = new MaterialUid($product);
        $this->event = new MaterialEventUid($event);

        if($offer)
        {
            $this->offer = new ProductOfferUid($offer);
            $this->offerConst = new ProductOfferConst($offer_const);
        }

        if($variation)
        {
            $this->variation = new ProductVariationUid($variation);
            $this->variationConst = new ProductVariationConst($variation_const);
        }

        if($modification)
        {
            $this->modification = new ProductModificationUid($modification);
            $this->modificationConst = new ProductModificationConst($modification_const);
        }

    }

    /**
     * Product
     */
    public function getProduct(): MaterialUid
    {
        return $this->product;
    }

    /**
     * Event
     */
    public function getEvent(): MaterialEventUid
    {
        return $this->event;
    }

    /**
     * Offer
     */
    public function getOffer(): ?ProductOfferUid
    {
        return $this->offer;
    }

    /**
     * OfferConst
     */
    public function getOfferConst(): ?ProductOfferConst
    {
        return $this->offerConst;
    }

    /**
     * Variation
     */
    public function getVariation(): ?ProductVariationUid
    {
        return $this->variation;
    }

    /**
     * VariationConst
     */
    public function getVariationConst(): ?ProductVariationConst
    {
        return $this->variationConst;
    }

    /**
     * Modification
     */
    public function getModification(): ?ProductModificationUid
    {
        return $this->modification;
    }

    /**
     * ModificationConst
     */
    public function getModificationConst(): ?ProductModificationConst
    {
        return $this->modificationConst;
    }
}
