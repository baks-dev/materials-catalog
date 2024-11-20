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

declare(strict_types=1);

namespace BaksDev\Materials\Catalog\Repository\CurrentProductIdentifier;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use InvalidArgumentException;

final class CurrentProductIdentifierRepository implements CurrentProductIdentifierInterface
{
    private MaterialEventUid|false $event = false;

    private ProductOfferUid|false $offer = false;

    private ProductVariationUid|false $variation = false;

    private ProductModificationUid|false $modification = false;

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

    public function forOffer(ProductOffer|ProductOfferUid|string|null|false $offer): self
    {
        if(is_null($offer) || $offer === false)
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new ProductOfferUid($offer);
        }

        if($offer instanceof ProductOffer)
        {
            $offer = $offer->getId();
        }

        $this->offer = $offer;

        return $this;
    }

    public function forVariation(ProductVariation|ProductVariationUid|string|null|false $variation): self
    {
        if(is_null($variation) || $variation === false)
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new ProductVariationUid($variation);
        }

        if($variation instanceof ProductVariation)
        {
            $variation = $variation->getId();
        }


        $this->variation = $variation;

        return $this;
    }

    public function forModification(ProductModification|ProductModificationUid|string|null|false $modification): self
    {
        if(is_null($modification) || $modification === false)
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new ProductModificationUid($modification);
        }

        if($modification instanceof ProductModification)
        {
            $modification = $modification->getId();
        }

        $this->modification = $modification;

        return $this;
    }


    /**
     * Метод возвращает активные идентификаторы продукта по событию и идентификаторов торгового предложения
     */
    public function find(): array|false
    {
        if(!$this->event instanceof MaterialEventUid)
        {
            throw new InvalidArgumentException('Необходимо вызвать метод forEvent и передать параметр $event');
        }

        /**
         * Определяем активное событие продукции
         */

        $current = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $current
            ->from(MaterialEvent::class, 'event')
            ->where('event.id = :event')
            ->setParameter(
                'event',
                $this->event,
                MaterialEventUid::TYPE
            );

        $current
            ->addSelect('product.id')
            ->addSelect('product.event')
            ->join(
                'event',
                Material::class,
                'product',
                'product.id = event.main'
            );

        if($this->offer)
        {
            $current->leftJoin(
                'product',
                ProductOffer::class,
                'offer',
                'offer.id = :offer'
            )
                ->setParameter(
                    'offer',
                    $this->offer,
                    ProductOfferUid::TYPE
                );


            $current
                ->addSelect('current_offer.id AS offer')
                ->addSelect('current_offer.const AS offer_const')
                ->leftJoin(
                    'offer',
                    ProductOffer::class,
                    'current_offer',
                    'current_offer.const = offer.const AND current_offer.event = product.event'
                );

            if($this->variation)
            {

                $current->leftJoin(
                    'offer',
                    ProductVariation::class,
                    'variation',
                    'variation.id = :variation AND variation.offer = offer.id'
                )
                    ->setParameter(
                        'variation',
                        $this->variation,
                        ProductVariationUid::TYPE
                    );

                $current
                    ->addSelect('current_variation.id AS variation')
                    ->addSelect('current_variation.const AS variation_const')
                    ->leftJoin(
                        'variation',
                        ProductVariation::class,
                        'current_variation',
                        'current_variation.const = variation.const AND current_variation.offer = current_offer.id'
                    );


                if($this->modification)
                {
                    $current
                        ->leftJoin(
                            'variation',
                            ProductModification::class,
                            'modification',
                            'modification.id = :modification AND modification.variation = variation.id'
                        )
                        ->setParameter(
                            'modification',
                            $this->modification,
                            ProductModificationUid::TYPE
                        );

                    $current
                        ->addSelect('current_modification.id AS modification')
                        ->addSelect('current_modification.const AS modification_const')
                        ->leftJoin(
                            'modification',
                            ProductModification::class,
                            'current_modification',
                            'current_modification.const = modification.const AND current_modification.variation = current_variation.id'
                        );
                }
            }
        }

        return $current
            ->enableCache('materials-catalog', 60)
            ->fetchAssociative();
    }
}
