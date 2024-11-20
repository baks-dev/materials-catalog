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
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\ProductOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use InvalidArgumentException;

final class CurrentProductIdentifierByConstRepository implements CurrentProductIdentifierByConstInterface
{
    private MaterialUid|false $product = false;

    private ProductOfferConst|false $offer = false;

    private ProductVariationConst|false $variation = false;

    private ProductModificationConst|false $modification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forProduct(MaterialUid|string $product): self
    {
        if(is_string($product))
        {
            $product = new MaterialUid($product);
        }

        $this->product = $product;

        return $this;
    }

    public function forOfferConst(ProductOfferConst|string|null|false $offer): self
    {
        if(is_null($offer) || $offer === false)
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new ProductOfferConst($offer);
        }

        $this->offer = $offer;

        return $this;
    }

    public function forVariationConst(ProductVariationConst|string|null|false $variation): self
    {
        if(is_null($variation) || $variation === false)
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new ProductVariationConst($variation);
        }


        $this->variation = $variation;

        return $this;
    }

    public function forModificationConst(ProductModificationConst|string|null|false $modification): self
    {
        if(is_null($modification) || $modification === false)
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new ProductModificationConst($modification);
        }

        $this->modification = $modification;

        return $this;
    }


    /**
     * Метод возвращает активные идентификаторы продукта по событию и идентификаторов торгового предложения
     */
    public function execute(): CurrentProductDTO|false
    {
        if(!$this->product instanceof MaterialUid)
        {
            throw new InvalidArgumentException('Необходимо вызвать метод forProduct и передать параметр $product');
        }

        /**
         * Определяем активное событие продукции
         */

        $current = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $current
            ->addSelect('product.id')
            ->addSelect('product.event')
            ->from(Material::class, 'product')
            ->where('product.id = :product')
            ->setParameter(
                'product',
                $this->product,
                MaterialUid::TYPE
            );


        if($this->offer)
        {
            $current
                ->addSelect('current_offer.id AS offer')
                ->addSelect('current_offer.const AS offer_const')
                ->leftJoin(
                    'product',
                    ProductOffer::class,
                    'current_offer',
                    'current_offer.const = :offer_const AND current_offer.event = product.event'
                )
                ->setParameter(
                    'offer_const',
                    $this->offer,
                    ProductOfferConst::TYPE
                );

            if($this->variation)
            {

                $current
                    ->addSelect('current_variation.id AS variation')
                    ->addSelect('current_variation.const AS variation_const')
                    ->leftJoin(
                        'current_offer',
                        ProductVariation::class,
                        'current_variation',
                        'current_variation.const = :variation_const AND current_variation.offer = current_offer.id'
                    )
                    ->setParameter(
                        'variation_const',
                        $this->variation,
                        ProductVariationConst::TYPE
                    );


                if($this->modification)
                {
                    $current
                        ->addSelect('current_modification.id AS modification')
                        ->addSelect('current_modification.const AS modification_const')
                        ->leftJoin(
                            'current_variation',
                            ProductModification::class,
                            'current_modification',
                            'current_modification.const = :modification_const AND current_modification.variation = current_variation.id'
                        )->setParameter(
                            'modification_const',
                            $this->modification,
                            ProductModificationConst::TYPE
                        );
                }
            }
        }


        return $current
            ->enableCache('materials-catalog', 60)
            ->fetchHydrate(CurrentProductDTO::class);

    }
}
