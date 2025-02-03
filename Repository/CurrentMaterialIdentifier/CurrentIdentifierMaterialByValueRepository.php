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
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use InvalidArgumentException;

final class CurrentIdentifierMaterialByValueRepository implements CurrentIdentifierMaterialByValueInterface
{
    private MaterialUid|false $material = false;

    private string|false $offer = false;

    private string|false $variation = false;

    private string|false $modification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forMaterial(MaterialUid|string $material): self
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        $this->material = $material;

        return $this;
    }

    public function forOfferValue(string|null|false $offer): self
    {
        if(is_null($offer) || $offer === false)
        {
            $this->offer = false;
            return $this;
        }

        $this->offer = $offer;

        return $this;
    }

    public function forVariationValue(string|null|false $variation): self
    {
        if(is_null($variation) || $variation === false)
        {
            $this->variation = false;
            return $this;
        }

        $this->variation = $variation;

        return $this;
    }

    public function forModificationValue(string|null|false $modification): self
    {
        if(is_null($modification) || $modification === false)
        {
            $this->modification = false;
            return $this;
        }

        $this->modification = $modification;

        return $this;
    }


    /**
     * Метод возвращает активные идентификаторы сырья по событию и идентификаторов торгового предложения
     */
    public function find(): CurrentMaterialDTO|false
    {
        if(!$this->material instanceof MaterialUid)
        {
            throw new InvalidArgumentException('Необходимо вызвать метод forMaterial и передать параметр $material');
        }

        /**
         * Определяем активное событие сырья
         */

        $current = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $current
            ->addSelect('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material')
            ->where('material.id = :material')
            ->setParameter(
                'material',
                $this->material,
                MaterialUid::TYPE
            );


        if($this->offer)
        {
            $current
                ->addSelect('current_offer.id AS offer')
                ->addSelect('current_offer.const AS offer_const')
                ->leftJoin(
                    'material',
                    MaterialOffer::class,
                    'current_offer',
                    'current_offer.value = :offer_value AND current_offer.event = material.event'
                )
                ->setParameter('offer_value', $this->offer);

            if($this->variation)
            {

                $current
                    ->addSelect('current_variation.id AS variation')
                    ->addSelect('current_variation.const AS variation_const')
                    ->leftJoin(
                        'current_offer',
                        MaterialVariation::class,
                        'current_variation',
                        'current_variation.value = :variation_value AND current_variation.offer = current_offer.id'
                    )
                    ->setParameter('variation_value', $this->variation);


                if($this->modification)
                {
                    $current
                        ->addSelect('current_modification.id AS modification')
                        ->addSelect('current_modification.const AS modification_const')
                        ->leftJoin(
                            'current_variation',
                            MaterialModification::class,
                            'current_modification',
                            'current_modification.value = :modification_value AND current_modification.variation = current_variation.id'
                        )
                        ->setParameter('modification_value', $this->modification);
                }
            }
        }


        return $current
            ->enableCache('materials-catalog', 60)
            ->fetchHydrate(CurrentMaterialDTO::class);

    }
}
