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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Price;

use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\MaterialVariationPriceInterface;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;

/** @see MaterialVariationPrice */
final class MaterialVariationPriceDTO implements MaterialVariationPriceInterface
{
    /** Стоимость */
    private ?Money $price = null;

    /** Валюта */
    private ?Currency $currency;


    public function __construct()
    {
        $this->currency = new Currency();
    }

    /** Стоимость */

    public function getPrice(): ?Money
    {
        return $this->price;
    }


    public function setPrice(Money|float|null $price): void
    {
        if($price !== null)
        {
            $price = $price instanceof Money ? $price : new Money($price);
        }

        $this->price = $price;
    }


    /** Валюта */

    public function getCurrency(): Currency
    {
        return $this->currency ?: new Currency();
    }


    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

}
