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

namespace BaksDev\Materials\Catalog\Repository\UpdateProductQuantity;

use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\ProductModificationUid;

interface AddProductQuantityInterface
{
    /** Указываем количество добавленного резерва */
    public function addReserve(int|false $reserve): self;

    /** Указываем количество добавленного остатка */
    public function addQuantity(int|false $quantity): self;

    public function forEvent(MaterialEvent|MaterialEventUid|string $event): self;

    public function forOffer(ProductOffer|ProductOfferUid|string|null $offer): self;

    public function forVariation(ProductVariation|ProductVariationUid|string|null $variation): self;

    public function forModification(ProductModification|ProductModificationUid|string|null $modification): self;

    /**
     * Метод обновляет указанное количество резерва либо остатка к продукции
     */
    public function update(): int|false;

}