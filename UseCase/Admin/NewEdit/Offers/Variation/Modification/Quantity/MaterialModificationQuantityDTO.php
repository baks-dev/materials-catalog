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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Quantity;

use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantityInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialModificationQuantity */
final class MaterialModificationQuantityDTO implements MaterialModificationQuantityInterface
{
    /** В наличии */
    #[Assert\NotBlank]
    #[Assert\Range(min: 0)]
    private ?int $quantity = 0;

    /** Резерв */
    #[Assert\NotBlank]
    #[Assert\Range(min: 0)]
    private ?int $reserve = 0;


    /** В наличии */

    public function getQuantity(): int
    {
        $this->quantity = $this->quantity && $this->quantity >= 0 ? $this->quantity : 0;

        return $this->quantity;
    }

    /** Резерв */

    public function getReserve(): int
    {
        $this->reserve = $this->reserve && $this->reserve >= 0 ? $this->reserve : 0;

        return $this->reserve;
    }
}
