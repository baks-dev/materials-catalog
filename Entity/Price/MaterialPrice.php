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

namespace BaksDev\Materials\Catalog\Entity\Price;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Measurement\Type\Measurement;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

// Стоимость сырья

#[ORM\Entity]
#[ORM\Table(name: 'material_price')]
class MaterialPrice extends EntityEvent
{
    /** ID события */
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: MaterialEvent::class, inversedBy: 'price')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private MaterialEvent $event;

    /** Стоимость */
    #[ORM\Column(type: Money::TYPE, nullable: true)]
    private ?Money $price;

    /** Валюта */
    #[ORM\Column(type: Currency::TYPE, length: 3, nullable: false)]
    private Currency $currency;

    /** В наличии */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private ?int $quantity = 0; // 0 - нет в наличии

    /** Резерв */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private ?int $reserve = 0;

    /** Единица измерения: */
    #[ORM\Column(type: Measurement::TYPE, length: 10, nullable: false)]
    private Measurement $measurement;


    public function __construct(MaterialEvent $event)
    {
        $this->event = $event;
        $this->currency = new Currency();
    }

    public function __toString(): string
    {
        return (string) $this->event;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialPriceInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialPriceInterface || $dto instanceof self)
        {
            if(null === $dto->getPrice())
            {
                return false;
            }

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /** Добавляем в резерв указанное количество */
    public function addReserve(int $reserve): bool
    {
        $this->reserve += $reserve;
        return $this->reserve <= $this->quantity;
    }


    /** Удаляем из резерва указанное количество */
    public function subReserve(?int $reserve): bool
    {
        if(empty($this->reserve) || $reserve > $this->reserve)
        {
            return false;
        }

        $this->reserve -= $reserve;

        return true;
    }

    /** Присваиваем резерву указанное количество */
    public function setReserve(?int $reserve): void
    {
        $this->reserve = $reserve ?: 0;
    }


    /** Удаляем из наличия указанное количество */
    public function subQuantity(?int $quantity): bool
    {
        if(empty($this->quantity) || $quantity > $this->quantity)
        {
            return false;
        }

        $this->quantity -= $quantity;

        return true;
    }

    /** Добавляем в наличие указанное количество */
    public function addQuantity(?int $quantity): void
    {
        $this->quantity ?: $this->quantity = 0;
        $this->quantity += $quantity;
    }

    /** Возвращает текущее состояние наличия */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /** Присваиваем наличию указанное количество */
    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity ?: 0;
    }

}
