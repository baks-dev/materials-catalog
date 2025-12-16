<?php
/*
 * Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Catalog\Repository\ExistMaterialBarcode;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use InvalidArgumentException;

final class ExistMaterialBarcodeRepository implements ExistMaterialBarcodeInterface
{
    public MaterialBarcode $barcode;

    private MaterialUid $material;

    private ?MaterialOfferConst $offer = null;

    private ?MaterialVariationConst $variation = null;

    private ?MaterialModificationConst $modification = null;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forBarcode(MaterialBarcode $barcode): self
    {
        $this->barcode = $barcode;
        return $this;
    }

    public function forMaterial(MaterialUid $material): self
    {
        $this->material = $material;
        return $this;
    }

    public function forOffer(?MaterialOfferConst $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function forVariation(?MaterialVariationConst $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    public function forModification(?MaterialModificationConst $modification): self
    {
        $this->modification = $modification;
        return $this;
    }

    public function exist(): bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        if(empty($this->barcode))
        {
            throw new InvalidArgumentException('Invalid argument Barcode');
        }

        if(empty($this->material))
        {
            throw new InvalidArgumentException('Invalid argument Material');
        }

        $dbal
            ->from(MaterialEvent::class, 'material_event')
            ->where('material_event.main = :material')
            ->setParameter('material', $this->material, MaterialUid::TYPE);

        if(false === ($this->offer instanceof MaterialOfferConst))
        {
            $dbal
                ->join(
                    'material_event',
                    MaterialInfo::class,
                    'material_info',
                    'material_info.event = material_event.id AND material_info.barcode = :barcode'
                )
                ->setParameter('barcode', $this->barcode, MaterialBarcode::TYPE);
        }

        if(
            ($this->offer instanceof MaterialOfferConst) &&
            false === ($this->variation instanceof MaterialVariationConst)
        )
        {
            $dbal
                ->join(
                    'material_event',
                    MaterialOffer::class,
                    'material_offer',
                    'material_offer.event = material_event.id AND
                    material_offer.const = :offer AND
                    material_offer.barcode = :barcode'
                )
                ->setParameter('offer', $this->offer, MaterialOfferConst::TYPE)
                ->setParameter('barcode', $this->barcode, MaterialBarcode::TYPE);
        }
        else
        {
            $dbal
                ->leftJoin(
                    'material_event',
                    MaterialOffer::class,
                    'material_offer',
                    'material_offer.event = material_event.id AND
                    material_offer.const = :offer'
                )
                ->setParameter('offer', $this->offer, MaterialOfferConst::TYPE);
        }

        if((
            $this->variation instanceof MaterialVariationConst) &&
            false === ($this->modification instanceof MaterialModificationConst)
        )
        {
            $dbal
                ->join(
                    'material_offer',
                    MaterialVariation::class,
                    'material_variation',
                    'material_variation.offer = material_offer.id AND
                    material_variation.const = :variation AND
                    material_variation.barcode = :barcode'
                )
                ->setParameter('variation', $this->variation, MaterialVariationConst::TYPE)
                ->setParameter('barcode', $this->barcode, MaterialBarcode::TYPE);
        }
        else
        {
            $dbal
                ->leftJoin(
                    'material_offer',
                    MaterialVariation::class,
                    'material_variation',
                    'material_variation.offer = material_offer.id AND
                    material_variation.const = :variation'
                )
                ->setParameter('variation', $this->variation, MaterialVariationConst::TYPE);
        }

        if($this->modification instanceof MaterialModificationConst)
        {
            $dbal
                ->join(
                    'material_variation',
                    MaterialModification::class,
                    'material_modification',
                    'material_modification.variation = material_variation.id AND
                    material_modification.const = :modification AND
                    material_variation.barcode = :barcode'
                )
                ->setParameter('modification', $this->modification, MaterialModificationConst::TYPE)
                ->setParameter('barcode', $this->barcode, MaterialBarcode::TYPE);
        }

        return $dbal->fetchExist();
    }
}