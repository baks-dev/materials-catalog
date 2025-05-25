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

namespace BaksDev\Materials\Catalog\Entity\Offers\Price;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Стоимость торгового предложения */


#[ORM\Entity]
#[ORM\Table(name: 'material_offer_price')]
class MaterialOfferPrice extends EntityEvent
{
    /** ID торгового предложения */
    #[Assert\NotBlank]
    #[Assert\Type(MaterialOffer::class)]
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: MaterialOffer::class, inversedBy: 'price')]
    #[ORM\JoinColumn(name: 'offer', referencedColumnName: "id")]
    private MaterialOffer $offer;

    /** Стоимость */
    #[Assert\Type(Money::class)]
    #[ORM\Column(type: Money::TYPE, nullable: true)]
    private ?Money $price;

    /** Валюта */
    #[Assert\Type(Currency::class)]
    #[ORM\Column(name: 'currency', type: Currency::TYPE, length: 3, nullable: false)]
    private Currency $currency;


    public function __construct(MaterialOffer $offer)
    {
        $this->offer = $offer;
        $this->currency = new Currency();
    }

    public function __toString(): string
    {
        return (string) $this->offer;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialOfferPriceInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialOfferPriceInterface || $dto instanceof self)
        {
            //            if(empty($dto->getPrice()?->getValue()))
            //            {
            //                return false;
            //            }

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}
