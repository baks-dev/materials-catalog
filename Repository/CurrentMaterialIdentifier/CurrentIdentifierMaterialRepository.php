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

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use InvalidArgumentException;

final class CurrentIdentifierMaterialRepository implements CurrentIdentifierMaterialInterface
{
    private MaterialEventUid|false $event = false;

    private MaterialOfferUid|false $offer = false;

    private MaterialVariationUid|false $variation = false;

    private MaterialModificationUid|false $modification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

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

    public function forOffer(MaterialOffer|MaterialOfferUid|string|null|false $offer): self
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

    public function forVariation(MaterialVariation|MaterialVariationUid|string|null|false $variation): self
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

    public function forModification(MaterialModification|MaterialModificationUid|string|null|false $modification): self
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
     * Метод возвращает активные идентификаторы сырья по событию и идентификаторов торгового предложения
     */
    public function find(): array|false
    {
        if(!$this->event instanceof MaterialEventUid)
        {
            throw new InvalidArgumentException('Необходимо вызвать метод forEvent и передать параметр $event');
        }

        /**
         * Определяем активное событие сырья
         */

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(MaterialEvent::class, 'event')
            ->where('event.id = :event')
            ->setParameter(
                'event',
                $this->event,
                MaterialEventUid::TYPE
            );

        $dbal
            ->addSelect('material.id')
            ->addSelect('material.event')
            ->join(
                'event',
                Material::class,
                'material',
                'material.id = event.main'
            );


        if($this->offer)
        {
            $dbal->leftJoin(
                'material',
                MaterialOffer::class,
                'offer',
                'offer.id = :offer'
            )
                ->setParameter(
                    'offer',
                    $this->offer,
                    MaterialOfferUid::TYPE
                );


            $dbal
                ->addSelect('current_offer.id AS offer')
                ->addSelect('current_offer.const AS offer_const')
                ->leftJoin(
                    'offer',
                    MaterialOffer::class,
                    'current_offer',
                    'current_offer.const = offer.const AND current_offer.event = material.event'
                );

            if($this->variation)
            {

                $dbal->leftJoin(
                    'offer',
                    MaterialVariation::class,
                    'variation',
                    'variation.id = :variation AND variation.offer = offer.id'
                )
                    ->setParameter(
                        'variation',
                        $this->variation,
                        MaterialVariationUid::TYPE
                    );

                $dbal
                    ->addSelect('current_variation.id AS variation')
                    ->addSelect('current_variation.const AS variation_const')
                    ->leftJoin(
                        'variation',
                        MaterialVariation::class,
                        'current_variation',
                        'current_variation.const = variation.const AND current_variation.offer = current_offer.id'
                    );


                if($this->modification)
                {
                    $dbal
                        ->leftJoin(
                            'variation',
                            MaterialModification::class,
                            'modification',
                            'modification.id = :modification AND modification.variation = variation.id'
                        )
                        ->setParameter(
                            'modification',
                            $this->modification,
                            MaterialModificationUid::TYPE
                        );

                    $dbal
                        ->addSelect('current_modification.id AS modification')
                        ->addSelect('current_modification.const AS modification_const')
                        ->leftJoin(
                            'modification',
                            MaterialModification::class,
                            'current_modification',
                            'current_modification.const = modification.const AND current_modification.variation = current_variation.id'
                        );
                }
            }
        }

        return $dbal
            ->enableCache('materials-catalog', 60)
            ->fetchAssociative();
    }
}
