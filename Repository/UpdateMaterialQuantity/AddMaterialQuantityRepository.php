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

namespace BaksDev\Materials\Catalog\Repository\UpdateMaterialQuantity;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Repository\CurrentMaterialIdentifier\CurrentIdentifierMaterialInterface;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use Doctrine\DBAL\ParameterType;
use InvalidArgumentException;

final class AddMaterialQuantityRepository implements AddMaterialQuantityInterface
{
    private int|false $quantity = false;

    private int|false $reserve = false;

    private MaterialEventUid|false $event = false;

    private MaterialOfferUid|false $offer = false;

    private MaterialVariationUid|false $variation = false;

    private MaterialModificationUid|false $modification = false;


    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly CurrentIdentifierMaterialInterface $currentMaterialIdentifier
    ) {}

    /** Указываем количество добавленного резерва */
    public function addReserve(int|false $reserve): self
    {
        $this->reserve = $reserve;
        return $this;
    }

    /** Указываем количество добавленного остатка */
    public function addQuantity(int|false $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function forEvent(MaterialEvent|MaterialEventUid|string $event): self
    {
        if($event instanceof MaterialEvent)
        {
            $event = $event->getId();
        }

        if(is_string($event))
        {
            $event = new MaterialEventUid($event);
        }

        $this->event = $event;

        return $this;
    }

    public function forOffer(MaterialOffer|MaterialOfferUid|string|false|null $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new MaterialOfferUid($offer);
        }

        if($offer instanceof MaterialOffer)
        {
            $offer = $offer->getId();
        }

        $this->offer = $offer;

        return $this;
    }

    public function forVariation(MaterialVariation|MaterialVariationUid|string|false|null $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new MaterialVariationUid($variation);
        }

        if($variation instanceof MaterialVariation)
        {
            $variation = $variation->getId();
        }


        $this->variation = $variation;

        return $this;
    }

    public function forModification(MaterialModification|MaterialModificationUid|string|false|null $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new MaterialModificationUid($modification);
        }

        if($modification instanceof MaterialModification)
        {
            $modification = $modification->getId();
        }

        $this->modification = $modification;

        return $this;
    }

    /**
     * Метод обновляет указанное количество резерва либо остатка к сырья
     */
    public function update(): int|false
    {
        if(!$this->event instanceof MaterialEventUid)
        {
            throw new InvalidArgumentException('Необходимо вызвать метод forEvent и передать параметр $event');
        }

        if($this->quantity === false && $this->reserve === false)
        {
            throw new InvalidArgumentException('Необходимо вызвать метод addQuantity || addReserve передав количество');
        }


        $result = $this->currentMaterialIdentifier
            ->forEvent($this->event)
            ->forOffer($this->offer)
            ->forVariation($this->variation)
            ->forModification($this->modification)
            ->find();

        /** Если идентификатор события не определен - не выполняем обновление (сырьё не найден) */
        if(!isset($result['event']))
        {
            return false;
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->update(MaterialPrice::class)
            ->where('event = :event')
            ->setParameter(
                'event',
                $result['event'],
                MaterialEventUid::TYPE
            );


        if($this->offer && isset($result['offer']))
        {
            $dbal
                ->update(MaterialOfferQuantity::class)
                ->where('offer = :offer')
                ->setParameter(
                    'offer',
                    $result['offer'],
                    MaterialOfferUid::TYPE
                );
        }

        if($this->variation && isset($result['variation']))
        {
            $dbal
                ->update(MaterialsVariationQuantity::class)
                ->where('variation = :variation')
                ->setParameter(
                    'variation',
                    $result['variation'],
                    MaterialVariationUid::TYPE
                );
        }


        if($this->variation && isset($result['modification']))
        {
            $dbal
                ->update(MaterialModificationQuantity::class)
                ->where('modification = :modification')
                ->setParameter(
                    'modification',
                    $result['modification'],
                    MaterialModificationUid::TYPE
                );
        }


        /** Если указан остаток - добавляем */
        if($this->quantity)
        {
            $dbal
                ->set('quantity', 'quantity + :quantity')
                ->setParameter('quantity', $this->quantity, ParameterType::INTEGER);
        }

        /** Если указан резерв - добавляем */
        if($this->reserve)
        {
            $dbal
                ->set('reserve', 'reserve + :reserve')
                ->setParameter('reserve', $this->reserve, ParameterType::INTEGER);

            /** @note !!! Добавить резерв можно только если имеются остатки */
            //$dbal->andWhere('(quantity - reserve) > 0');
        }

        return (int) $dbal->executeStatement();
    }
}
