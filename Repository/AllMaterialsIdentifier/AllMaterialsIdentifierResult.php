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

namespace BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier;

use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;

final readonly class AllMaterialsIdentifierResult
{
    public function __construct(
        private string $material_id,
        private string $material_event,
        private ?string $offer_id,
        private ?string $offer_const,
        private ?string $variation_id,
        private ?string $variation_const,
        private ?string $modification_id,
        private ?string $modification_const,
    ) {}

    public function getMaterialId(): MaterialUid
    {
        return new MaterialUid($this->material_id);
    }

    public function getMaterialEvent(): MaterialEventUid
    {
        return new MaterialEventUid($this->material_event);
    }

    public function getOfferId(): ?MaterialOfferUid
    {
        return false === empty($this->offer_id) ? new MaterialOfferUid($this->offer_id) : null;
    }

    public function getOfferConst(): ?MaterialOfferConst
    {
        return false === empty($this->offer_const) ? new MaterialOfferConst($this->offer_const) : null;
    }

    public function getVariationId(): ?MaterialVariationUid
    {
        return false === empty($this->variation_id) ? new MaterialVariationUid($this->variation_id) : null;
    }

    public function getVariationConst(): ?MaterialVariationConst
    {
        return false === empty($this->variation_const) ? new MaterialVariationConst($this->variation_const) : null;
    }

    public function getModificationId(): ?MaterialModificationUid
    {
        return false === empty($this->modification_id) ? new MaterialModificationUid($this->modification_id) : null;
    }

    public function getModificationConst(): ?MaterialModificationConst
    {
        return false === empty($this->modification_const) ? new MaterialModificationConst($this->modification_const) : null;
    }
}