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

namespace BaksDev\Materials\Catalog\UseCase\Admin\Invariable;

use BaksDev\Materials\Catalog\Type\Invariable\MaterialInvariableUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialInvariable */
final class MaterialInvariableDTO //implements MaterialInvariableInterface
{
    /**
     * Идентификатор сущности
     */
    #[Assert\Uuid]
    private ?MaterialInvariableUid $id = null;

    /** ID сырья (не уникальное) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private MaterialUid $material;

    /** Константа ТП */
    #[Assert\Uuid]
    private ?MaterialOfferConst $offer = null;

    /** Константа множественного варианта */
    #[Assert\Uuid]
    private ?MaterialVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[Assert\Uuid]
    private ?MaterialModificationConst $modification = null;


    public function setId(?MaterialInvariableUid $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Id
     */
    public function getMain(): ?MaterialInvariableUid
    {
        return $this->id;
    }

    /**
     * Material
     */
    public function getMaterial(): MaterialUid
    {
        return $this->material;
    }

    public function setMaterial(MaterialUid $material): self
    {
        $this->material = $material;
        return $this;
    }

    /**
     * Offer
     */
    public function getOffer(): ?MaterialOfferConst
    {
        return $this->offer;
    }

    public function setOffer(?MaterialOfferConst $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    /**
     * Variation
     */
    public function getVariation(): ?MaterialVariationConst
    {
        return $this->variation;
    }

    public function setVariation(?MaterialVariationConst $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    /**
     * Modification
     */
    public function getModification(): ?MaterialModificationConst
    {
        return $this->modification;
    }

    public function setModification(?MaterialModificationConst $modification): self
    {
        $this->modification = $modification;
        return $this;
    }

}