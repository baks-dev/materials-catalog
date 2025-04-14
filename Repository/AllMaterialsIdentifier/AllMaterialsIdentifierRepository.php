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

namespace BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use Generator;

final class AllMaterialsIdentifierRepository implements AllMaterialsIdentifierInterface
{
    private MaterialUid|false $material = false;

    private MaterialOfferConst|false $offerConst = false;

    private MaterialVariationConst|false $offerVariation = false;

    private MaterialModificationConst|false $offerModification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forMaterial(Material|MaterialUid|string $material): self
    {
        if(is_string($material))
        {
            $material = new MaterialUid($material);
        }

        if($material instanceof Material)
        {
            $material = $material->getId();
        }

        $this->material = $material;

        return $this;
    }

    public function forOfferConst(MaterialOfferConst|string|null|false $offerConst): self
    {
        if(empty($offerConst))
        {
            $this->offerConst = false;
            return $this;
        }

        if(is_string($offerConst))
        {
            $offerConst = new MaterialOfferConst($offerConst);
        }

        $this->offerConst = $offerConst;

        return $this;
    }

    public function forVariationConst(MaterialVariationConst|string|null|false $offerVariation): self
    {
        if(empty($offerVariation))
        {
            $this->offerVariation = false;
            return $this;
        }

        if(is_string($offerVariation))
        {
            $offerVariation = new MaterialVariationConst($offerVariation);
        }

        $this->offerVariation = $offerVariation;

        return $this;
    }

    public function forModificationConst(MaterialModificationConst|string|null|false $offerModification): self
    {
        if(empty($offerModification))
        {
            $this->offerModification = false;
            return $this;
        }

        if(is_string($offerModification))
        {
            $offerModification = new MaterialModificationConst($offerModification);
        }

        $this->offerModification = $offerModification;

        return $this;
    }

    /**
     * Метод возвращает все идентификаторы сырья с её торговыми предложениями
     * @return Generator<array{
     *  "material_id",
     *  "material_event" ,
     *  "offer_id" ,
     *  "offer_const",
     *  "variation_id" ,
     *  "variation_const" ,
     *  "modification_id",
     *  "modification_const"}
     *  >|false }
     */
    public function findAll(): Generator|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('material.id AS material_id')
            ->addSelect('material.event AS material_event')
            ->from(Material::class, 'material');

        if($this->material)
        {
            $dbal
                ->where('material.id = :material')
                ->setParameter(
                    'material',
                    $this->material,
                    MaterialUid::TYPE
                );
        }


        $dbal
            ->addSelect('offer.id AS offer_id')
            ->addSelect('offer.const AS offer_const');

        if($this->offerConst)
        {
            $dbal->join(
                'material',
                MaterialOffer::class,
                'offer',
                'offer.event = material.event AND offer.const = :offer_const'
            )
                ->setParameter(
                    'offer_const',
                    $this->offerConst,
                    MaterialOfferConst::TYPE
                );
        }
        else
        {
            $dbal->leftJoin(
                'material',
                MaterialOffer::class,
                'offer',
                'offer.event = material.event'
            );
        }


        $dbal
            ->addSelect('variation.id AS variation_id')
            ->addSelect('variation.const AS variation_const');

        if($this->offerVariation)
        {
            $dbal->join(
                'offer',
                MaterialVariation::class,
                'variation',
                'variation.offer = offer.id AND variation.const = :variation_const'
            )
                ->setParameter(
                    'variation_const',
                    $this->offerVariation,
                    MaterialVariationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'offer',
                    MaterialVariation::class,
                    'variation',
                    'variation.offer = offer.id'
                );
        }

        $dbal
            ->addSelect('modification.id AS modification_id')
            ->addSelect('modification.const AS modification_const');

        if($this->offerModification)
        {
            $dbal
                ->join(
                    'variation',
                    MaterialModification::class,
                    'modification',
                    'modification.variation = variation.id AND modification.const = :modification_const'
                )
                ->setParameter(
                    'modification_const',
                    $this->offerModification,
                    MaterialModificationConst::TYPE
                );
        }
        else
        {
            $dbal
                ->leftJoin(
                    'variation',
                    MaterialModification::class,
                    'modification',
                    'modification.variation = variation.id'
                );
        }

        return $dbal->fetchAllGenerator();
    }
}
