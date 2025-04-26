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

namespace BaksDev\Materials\Catalog\Repository\MaterialModificationChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\Trans\CategoryMaterialModificationTrans;
use Generator;

final class MaterialModificationChoiceRepository implements MaterialModificationChoiceInterface
{
    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly DBALQueryBuilder $DBALQueryBuilder
    ) {}

    /**
     * Метод возвращает все постоянные идентификаторы CONST модификаций множественных вариантов торговых предложений сырья
     */
    public function fetchMaterialModificationConstByVariationConst(MaterialVariationConst|string $const): Generator
    {
        if(is_string($const))
        {
            $const = new MaterialVariationConst($const);
        }


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(MaterialVariation::class, 'variation')
            ->where('variation.const = :const')
            ->setParameter(
                key: 'const',
                value: $const,
                type: MaterialVariationConst::TYPE
            );

        $dbal->join(
            'variation',
            MaterialOffer::class,
            'offer',
            'offer.id = variation.offer'
        );

        $dbal->join(
            'offer',
            Material::class,
            'material',
            'material.event = offer.event'
        );

        $dbal->join(
            'variation',
            MaterialModification::class,
            'modification',
            'modification.variation = variation.id'
        );

        // Тип торгового предложения

        $dbal->join(
            'modification',
            CategoryMaterialModification::class,
            'category_modification',
            'category_modification.id = modification.category_modification'
        );

        $dbal->leftJoin(
            'category_modification',
            CategoryMaterialModificationTrans::class,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
        );

        /** Свойства конструктора объекта гидрации */

        $dbal
            ->addSelect('modification.const AS value')
            ->addSelect('modification.value AS attr')
            ->addSelect('category_modification_trans.name AS option')
            ->addSelect('category_modification.reference AS property');


        $dbal->orderBy('modification.value');

        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAllHydrate(MaterialModificationConst::class);

    }


    /**
     * Метод возвращает все идентификаторы модификаций множественных вариантов торговых предложений сырья
     */
    public function fetchMaterialModificationByVariation(MaterialVariationUid $variation): ?array
    {
        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $select = sprintf('new %s(
            modification.id, 
            modification.value, 
            trans.name, 
            category_modification.reference
        )', MaterialModificationUid::class);

        $qb->select($select);

        $qb
            ->from(MaterialVariation::class, 'variation')
            ->where('variation.id = :variation')
            ->setParameter(
                key: 'variation',
                value: $variation,
                type: MaterialVariationUid::TYPE
            );

        $qb->join(
            MaterialOffer::class,
            'offer',
            'WITH',
            'offer.id = variation.offer'
        );

        $qb->join(
            Material::class,
            'material',
            'WITH',
            'material.event = offer.event'
        );


        $qb->join(
            MaterialModification::class,
            'modification',
            'WITH',
            'modification.variation = variation.id'
        );

        // Тип торгового предложения

        $qb->join(
            CategoryMaterialModification::class,
            'category_modification',
            'WITH',
            'category_modification.id = modification.categoryModification'
        );

        $qb->leftJoin(
            CategoryMaterialModificationTrans::class,
            'trans',
            'WITH',
            'trans.modification = category_modification.id AND trans.local = :local'
        );

        /* Кешируем результат ORM */
        return $qb->getResult();

    }

    /**
     * Метод возвращает все идентификаторы модификаций множественных вариантов торговых предложений сырья
     */
    public function fetchMaterialModificationExistsByVariation(MaterialVariationUid|string $variation): Generator
    {
        if(is_string($variation))
        {
            $variation = new MaterialVariationUid($variation);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(MaterialVariation::class, 'variation')
            ->where('variation.id = :variation')
            ->setParameter(
                key: 'variation',
                value: $variation,
                type: MaterialVariationUid::TYPE
            );

        $dbal->join(
            'variation',
            MaterialOffer::class,
            'offer',
            'offer.id = variation.offer'
        );

        $dbal->join(
            'offer',
            Material::class,
            'material',
            'material.event = offer.event'
        );


        $dbal->join(
            'variation',
            MaterialModification::class,
            'modification',
            'modification.variation = variation.id'
        );

        // Тип торгового предложения

        $dbal->join(
            'modification',
            CategoryMaterialModification::class,
            'category_modification',
            'category_modification.id = modification.category_modification'
        );

        $dbal->leftJoin(
            'category_modification',
            CategoryMaterialModificationTrans::class,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id
             AND category_modification_trans.local = :local'
        );


        $dbal
            ->addSelect('SUM(modification_quantity.quantity - modification_quantity.reserve) AS option')
            ->join(
                'modification',
                MaterialModificationQuantity::class,
                'modification_quantity',
                'modification_quantity.modification = modification.id AND modification_quantity.quantity > 0 '
            );


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('modification.id AS value');
        $dbal->addSelect('category_modification_trans.name AS property');
        $dbal->addSelect('category_modification.reference AS characteristic');

        $dbal->allGroupByExclude();

        return $dbal->fetchAllHydrate(MaterialModificationUid::class);

    }


}
