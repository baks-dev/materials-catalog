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

namespace BaksDev\Materials\Catalog\Repository\CurrentMaterialIdentifier;

use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class CurrentMaterialResult
{
    public function __construct(
        private string $id,
        private string $event,

        /** Торговое предложение */
        private ?string $offer = null,
        private ?string $offer_const = null,

        /** Множественный вариант торгового предложения */
        private ?string $variation = null,
        private ?string $variation_const = null,

        /** Модификация множественного варианта торгового предложения */
        private ?string $modification = null,
        private ?string $modification_const = null,
    ) {}

    /**
     * Material
     */
    public function getMaterial(): MaterialUid
    {
        return new MaterialUid($this->id);
    }

    /**
     * Event
     */
    public function getEvent(): MaterialEventUid
    {
        return new MaterialEventUid($this->event);
    }

    /**
     * Offer
     */
    public function getOffer(): MaterialOfferUid|false
    {
        return $this->offer ? new MaterialOfferUid($this->offer) : false;
    }

    /**
     * OfferConst
     */
    public function getOfferConst(): MaterialOfferConst|false
    {
        return $this->offer_const ? new MaterialOfferConst($this->offer_const) : false;
    }

    /**
     * Variation
     */
    public function getVariation(): MaterialVariationUid|false
    {
        return $this->variation ? new MaterialVariationUid($this->variation) : false;
    }

    /**
     * VariationConst
     */
    public function getVariationConst(): MaterialVariationConst|false
    {
        return $this->variation_const ? new MaterialVariationConst($this->variation_const) : false;
    }

    /**
     * Modification
     */
    public function getModification(): MaterialModificationUid|false
    {
        return $this->modification ? new MaterialModificationUid($this->modification) : false;
    }

    /**
     * ModificationConst
     */
    public function getModificationConst(): MaterialModificationConst|false
    {
        return $this->modification_const ? new MaterialModificationConst($this->modification_const) : false;
    }
}
