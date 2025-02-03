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

namespace BaksDev\Materials\Catalog\Repository\MaterialByModification;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use InvalidArgumentException;

/**
 * Класс возвращает идентификаторы сырья по модификации
 */
final class MaterialByModificationRepository implements MaterialByModificationInterface
{
    private ?array $data = null;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Класс возвращает идентификаторы сырья по модификации
     */

    public function findModification(MaterialModificationUid|MaterialModificationConst $modification): self
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);


        if($modification instanceof MaterialModificationConst)
        {
            $dbal
                ->addSelect('modification.id AS modification_id')
                ->addSelect('modification.const AS modification_const')
                ->from(MaterialModification::class, 'modification')
                ->where('modification.const = :const')
                ->setParameter('const', $modification, MaterialModificationConst::TYPE);
        }

        if($modification instanceof MaterialModificationUid)
        {

            $dbal
                ->from(MaterialModification::class, 'mod')
                ->where('mod.id = :modification')
                ->setParameter('modification', $modification, MaterialModificationUid::TYPE);

            $dbal
                ->addSelect('modification.id AS modification_id')
                ->addSelect('modification.const AS modification_const')
                ->join(
                    'mod',
                    MaterialModification::class,
                    'modification',
                    'modification.const = mod.const'
                );
        }

        $dbal
            ->addSelect('variation.id AS variation_id')
            ->addSelect('variation.const AS variation_const')
            ->join(
                'modification',
                MaterialVariation::class,
                'variation',
                'variation.id = modification.variation'
            );

        $dbal
            ->addSelect('offer.id AS offer_id')
            ->addSelect('offer.const AS offer_const')
            ->join(
                'variation',
                MaterialOffer::class,
                'offer',
                'offer.id = variation.offer'
            );

        $dbal
            ->join(
                'offer',
                MaterialEvent::class,
                'event',
                'event.id = offer.event'
            );

        $dbal
            ->addSelect('material.id AS id')
            ->addSelect('material.event AS event_id')
            ->join(
                'event',
                Material::class,
                'material',
                'material.event = event.id'
            );

        $result = $dbal
            ->enableCache('materials-catalog', 30)
            ->fetchAllAssociative();

        $this->data = count($result) === 1 ? current($result) : throw new InvalidArgumentException('Many Result');

        return $this;
    }

    public function getMaterial(): ?MaterialUid
    {
        return isset($this->data['id']) ? new MaterialUid($this->data['id']) : null;
    }

    public function getEvent(): ?MaterialEventUid
    {
        return isset($this->data['event_id']) ? new MaterialEventUid($this->data['event_id']) : null;
    }


    public function getOffer(): ?MaterialOfferUid
    {
        return isset($this->data['offer_id']) ? new MaterialOfferUid($this->data['offer_id']) : null;
    }

    public function getOfferConst(): ?MaterialOfferConst
    {
        return isset($this->data['offer_const']) ? new MaterialOfferConst($this->data['offer_const']) : null;
    }


    public function getVariation(): ?MaterialVariationUid
    {
        return isset($this->data['variation_id']) ? new MaterialVariationUid($this->data['variation_id']) : null;
    }

    public function getVariationConst(): ?MaterialVariationConst
    {
        return isset($this->data['variation_const']) ? new MaterialVariationConst($this->data['variation_const']) : null;
    }


    public function getModification(): ?MaterialModificationUid
    {
        return isset($this->data['modification_id']) ? new MaterialModificationUid($this->data['modification_id']) : null;
    }

    public function getModificationConst(): ?MaterialModificationConst
    {
        return isset($this->data['modification_const']) ? new MaterialModificationConst($this->data['modification_const']) : null;
    }
}
