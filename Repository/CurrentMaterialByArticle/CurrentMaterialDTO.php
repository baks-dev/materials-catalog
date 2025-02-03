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

namespace BaksDev\Materials\Catalog\Repository\CurrentMaterialByArticle;

use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;

final readonly class CurrentMaterialDTO
{
    public function __construct(
        private string $material,
        private string $event,

        /** Торговое предложение */
        private ?string $offer,
        private ?string $offer_const,

        /** Множественный вариант торгового предложения */
        private ?string $variation,
        private ?string $variation_const,

        /** Модификация множественного варианта торгового предложения */
        private ?string $modification,
        private ?string $modification_const,
    ) {}

    /**
     * Material
     */
    public function getMaterial(): MaterialUid
    {
        return new MaterialUid($this->material);
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
    public function getOffer(): ?MaterialOfferUid
    {
        return $this->offer ? new MaterialOfferUid($this->offer) : null;
    }

    /**
     * OfferConst
     */
    public function getOfferConst(): ?MaterialOfferConst
    {
        return $this->offer_const ? new MaterialOfferConst($this->offer_const) : null;
    }

    /**
     * Variation
     */
    public function getVariation(): ?MaterialVariationUid
    {
        return $this->variation ? new MaterialVariationUid($this->variation) : null;
    }

    /**
     * VariationConst
     */
    public function getVariationConst(): ?MaterialVariationConst
    {
        return $this->variation_const ? new MaterialVariationConst($this->variation_const) : null;
    }

    /**
     * Modification
     */
    public function getModification(): ?MaterialModificationUid
    {
        return $this->modification ? new MaterialModificationUid($this->modification) : null;
    }

    /**
     * ModificationConst
     */
    public function getModificationConst(): ?MaterialModificationConst
    {
        return $this->modification_const ? new MaterialModificationConst($this->modification_const) : null;
    }
}