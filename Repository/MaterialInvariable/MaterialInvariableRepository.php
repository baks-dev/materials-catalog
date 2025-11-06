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

namespace BaksDev\Materials\Catalog\Repository\MaterialInvariable;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\MaterialInvariable;
use BaksDev\Materials\Catalog\Type\Invariable\MaterialInvariableUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use InvalidArgumentException;


final class MaterialInvariableRepository implements MaterialInvariableInterface
{

    /** ID сырья */
    private MaterialUid|false $material = false;

    /** Константа ТП */
    private MaterialOfferConst|false $offer = false;

    /** Константа множественного варианта */
    private MaterialVariationConst|false $variation = false;

    /** Константа модификации множественного варианта */
    private MaterialModificationConst|false $modification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function material(MaterialUid|string $material): self
    {
        if($material instanceof MaterialUid)
        {
            $this->material = $material;
        }

        if(is_string($material))
        {
            $this->material = new MaterialUid($material);
        }

        return $this;
    }

    public function offer(MaterialOfferConst|string|false|null $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if($offer instanceof MaterialOfferConst)
        {
            $this->offer = $offer;
        }

        if(is_string($offer))
        {
            $this->offer = new MaterialOfferConst($offer);
        }

        return $this;
    }

    public function variation(MaterialVariationConst|string|false|null $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if($variation instanceof MaterialVariationConst)
        {
            $this->variation = $variation;
        }

        if(is_string($variation))
        {
            $this->variation = new MaterialVariationConst($variation);
        }

        return $this;
    }

    public function modification(MaterialModificationConst|string|false|null $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if($modification instanceof MaterialModificationConst)
        {
            $this->modification = $modification;
        }

        if(is_string($modification))
        {
            $this->modification = new MaterialModificationConst($modification);
        }

        return $this;
    }

    /**
     * Метод возвращает идентификатор Invariable сырья
     */
    public function find(): MaterialInvariableUid|false
    {
        if(false === ($this->material instanceof MaterialUid))
        {
            throw new InvalidArgumentException('Material not found.');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->select('invariable.id');

        $dbal
            ->from(MaterialInvariable::class, 'invariable')
            ->where('invariable.material = :material')
            ->setParameter('material', $this->material, MaterialUid::TYPE);

        if(false === $this->offer)
        {
            $dbal->andWhere('invariable.offer IS NULL');
        }
        else
        {
            $dbal
                ->andWhere('invariable.offer = :offer')
                ->setParameter('offer', $this->offer, MaterialOfferConst::TYPE);
        }

        if(false === $this->variation)
        {
            $dbal->andWhere('invariable.variation IS NULL');
        }
        else
        {
            $dbal
                ->andWhere('invariable.variation = :variation')
                ->setParameter('variation', $this->variation, MaterialVariationConst::TYPE);
        }

        if(false === $this->modification)
        {
            $dbal->andWhere('invariable.modification IS NULL');
        }
        else
        {
            $dbal
                ->andWhere('invariable.modification = :modification')
                ->setParameter('modification', $this->modification, MaterialModificationConst::TYPE);
        }

        $invariable = $dbal
            //->enableCache('materials-catalog', 86400)
            ->fetchOne();

        return $invariable ? new MaterialInvariableUid($invariable) : false;
    }
}