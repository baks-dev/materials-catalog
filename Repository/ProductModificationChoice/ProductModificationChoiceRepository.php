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

namespace BaksDev\Materials\Catalog\Repository\ProductModificationChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\ProductModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use Generator;

final class ProductModificationChoiceRepository implements ProductModificationChoiceInterface
{
    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly DBALQueryBuilder $DBALQueryBuilder
    ) {}

    /**
     * Метод возвращает все постоянные идентификаторы CONST модификаций множественных вариантов торговых предложений продукта
     */
    public function fetchProductModificationConstByVariationConst(ProductVariationConst|string $const): Generator
    {
        if(is_string($const))
        {
            $const = new ProductVariationConst($const);
        }


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(ProductVariation::class, 'variation')
            ->where('variation.const = :const')
            ->setParameter('const', $const, ProductVariationConst::TYPE);

        $dbal->join(
            'variation',
            ProductOffer::class,
            'offer',
            'offer.id = variation.offer'
        );

        $dbal->join(
            'offer',
            Material::class,
            'product',
            'product.event = offer.event'
        );

        $dbal->join(
            'variation',
            ProductModification::class,
            'modification',
            'modification.variation = variation.id'
        );

        // Тип торгового предложения

        $dbal->join(
            'modification',
            CategoryProductModification::class,
            'category_modification',
            'category_modification.id = modification.category_modification'
        );

        $dbal->leftJoin(
            'category_modification',
            CategoryProductModificationTrans::class,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
        );

        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('modification.const AS value');
        $dbal->addSelect('modification.value AS attr');

        $dbal->addSelect('category_modification_trans.name AS option');
        $dbal->addSelect('category_modification.reference AS property');

        $dbal->addSelect('modification.postfix AS characteristic');


        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAllHydrate(ProductModificationConst::class);

    }


    /**
     * Метод возвращает все идентификаторы модификаций множественных вариантов торговых предложений продукта
     */
    public function fetchProductModificationByVariation(ProductVariationUid $variation): ?array
    {
        $qb = $this->ORMQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $select = sprintf('new %s(
            modification.id, 
            modification.value, 
            trans.name, 
            category_modification.reference
        )', ProductModificationUid::class);

        $qb->select($select);

        $qb->from(ProductVariation::class, 'variation');
        $qb->where('variation.id = :variation');
        $qb->setParameter('variation', $variation, ProductVariationUid::TYPE);

        $qb->join(
            ProductOffer::class,
            'offer',
            'WITH',
            'offer.id = variation.offer'
        );

        $qb->join(
            Material::class,
            'product',
            'WITH',
            'product.event = offer.event'
        );


        $qb->join(
            ProductModification::class,
            'modification',
            'WITH',
            'modification.variation = variation.id'
        );

        // Тип торгового предложения

        $qb->join(
            CategoryProductModification::class,
            'category_modification',
            'WITH',
            'category_modification.id = modification.categoryModification'
        );

        $qb->leftJoin(
            CategoryProductModificationTrans::class,
            'trans',
            'WITH',
            'trans.modification = category_modification.id AND trans.local = :local'
        );

        /* Кешируем результат ORM */
        return $qb->enableCache('materials-catalog', 86400)->getResult();

    }

    /**
     * Метод возвращает все идентификаторы модификаций множественных вариантов торговых предложений продукта
     */
    public function fetchProductModificationExistsByVariation(ProductVariationUid|string $variation): Generator
    {
        if(is_string($variation))
        {
            $variation = new ProductVariationUid($variation);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->from(ProductVariation::class, 'variation')
            ->where('variation.id = :variation')
            ->setParameter('variation', $variation, ProductVariationUid::TYPE);

        $dbal->join(
            'variation',
            ProductOffer::class,
            'offer',
            'offer.id = variation.offer'
        );

        $dbal->join(
            'offer',
            Material::class,
            'product',
            'product.event = offer.event'
        );


        $dbal->join(
            'variation',
            ProductModification::class,
            'modification',
            'modification.variation = variation.id'
        );

        // Тип торгового предложения

        $dbal->join(
            'modification',
            CategoryProductModification::class,
            'category_modification',
            'category_modification.id = modification.category_modification'
        );

        $dbal->leftJoin(
            'category_modification',
            CategoryProductModificationTrans::class,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id
             AND category_modification_trans.local = :local'
        );


        $dbal
            ->addSelect('SUM(modification_quantity.quantity - modification_quantity.reserve) AS option')
            ->join(
                'modification',
                ProductModificationQuantity::class,
                'modification_quantity',
                'modification_quantity.modification = modification.id AND modification_quantity.quantity > 0 '
            );


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('modification.id AS value');
        $dbal->addSelect("CONCAT(modification.value, ' ', modification.postfix) AS attr");

        $dbal->addSelect('category_modification_trans.name AS property');
        $dbal->addSelect('category_modification.reference AS characteristic');

        $dbal->allGroupByExclude();

        return $dbal->fetchAllHydrate(ProductModificationUid::class);

    }


}
