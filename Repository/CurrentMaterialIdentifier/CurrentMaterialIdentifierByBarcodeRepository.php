<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
 *
 */

declare(strict_types=1);

namespace BaksDev\Materials\Catalog\Repository\CurrentMaterialIdentifier;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use Doctrine\DBAL\ArrayParameterType;
use InvalidArgumentException;

final class CurrentMaterialIdentifierByBarcodeRepository implements CurrentMaterialIdentifierByBarcodeInterface
{
    private array|false $barcodes = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder
    ) {}

    /** Массив штрихкодов */
    public function byBarcodes(array $barcodes): self
    {
        /** Проверка на пустой массив */
        if(true === empty($barcodes))
        {
            $this->barcodes = false;
            return $this;
        }

        /** Проверяем, что в массив штихкодов не было передано пустое значение */
        $containEmptyBarcode = array_any($barcodes, function($barcode) {
            return true === empty($barcode);
        });

        if(true === $containEmptyBarcode)
        {
            $this->barcodes = false;
            return $this;
        }

        $this->barcodes = $barcodes;
        return $this;
    }

    public function find(): CurrentMaterialResult|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        if(false === $this->barcodes)
        {
            throw new InvalidArgumentException('Не передан обязательный параметр запроса barcodes');
        }

        $dbal
            ->select('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material');

        $dbal
            ->join(
                'material',
                MaterialEvent::class,
                'material_event',
                'material_event.id = material.event'
            );

        /** Material */

        $dbal
            ->leftJoin(
                'material_event',
                MaterialInfo::class,
                'material_info',
                'material_info.event = material_event.id'
            );

        $dbal
            ->where('material_info.barcode IN (:barcodes)')
            ->setParameter('barcodes', $this->barcodes, ArrayParameterType::STRING);

        /** Offer */

        $dbal
            ->addSelect('material_offer.id AS offer')
            ->addSelect('material_offer.const AS offer_const')
            ->leftJoin(
                'material_event',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material_event.id'
            );

        $dbal
            ->orWhere('material_offer.barcode IN (:barcodes)')
            ->setParameter('barcodes', $this->barcodes, ArrayParameterType::STRING);

        /** Variation */

        $dbal
            ->addSelect('material_variation.id AS variation')
            ->addSelect('material_variation.const AS variation_const')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id'
            );

        $dbal
            ->orWhere('material_variation.barcode IN (:barcodes)')
            ->setParameter('barcodes', $this->barcodes, ArrayParameterType::STRING);

        /** Modification */

        $dbal
            ->addSelect('material_modification.id AS modification')
            ->addSelect('material_modification.const AS modification_const')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id'
            );

        $dbal
            ->orWhere('material_modification.barcode IN (:barcodes)')
            ->setParameter('barcodes', $this->barcodes, ArrayParameterType::STRING);

        return $dbal
            //            ->enableCache('materials-catalog', 86400)
            ->fetchHydrate(CurrentMaterialResult::class);
    }
}