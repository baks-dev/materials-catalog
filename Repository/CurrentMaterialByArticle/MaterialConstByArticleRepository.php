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

namespace BaksDev\Materials\Catalog\Repository\CurrentMaterialByArticle;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;

final readonly class MaterialConstByArticleRepository implements MaterialConstByArticleInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает активные идентификаторы сырья
     */
    public function find(string $article): CurrentMaterialDTO|false
    {

        /** Поиск артикула INFO */

        $dbalInfo = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbalInfo->select('material.id AS material');
        $dbalInfo->addSelect('material.event');
        $dbalInfo->addSelect('NULL::uuid  AS offer');
        $dbalInfo->addSelect('NULL::uuid  AS offer_const');
        $dbalInfo->addSelect('NULL::uuid  AS variation');
        $dbalInfo->addSelect('NULL::uuid  AS variation_const');
        $dbalInfo->addSelect('NULL::uuid  AS modification');
        $dbalInfo->addSelect('NULL::uuid  AS modification_const');

        $dbalInfo->from(MaterialInfo::class, 'info');

        $dbalInfo->join(
            'info',
            Material::class, 'material',
            'material.id = info.material'
        );

        $dbalInfo->where('info.article = :article');


        /** Поиск артикула OFFER */

        $dbalOffer = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbalOffer->select('material.id AS material');
        $dbalOffer->addSelect('material.event');
        $dbalOffer->addSelect('offer.id AS offer');
        $dbalOffer->addSelect('offer.const AS offer_const');
        $dbalOffer->addSelect('NULL::uuid  AS variation');
        $dbalOffer->addSelect('NULL::uuid  AS variation_const');
        $dbalOffer->addSelect('NULL::uuid  AS modification');
        $dbalOffer->addSelect('NULL::uuid  AS modification_const');

        $dbalOffer
            ->from(MaterialOffer::class, 'offer')
            ->where('offer.article = :article');

        $dbalOffer->join(
            'offer',
            Material::class, 'material',
            'material.event = offer.event'
        );


        /** Поиск артикула VARIATION */

        $dbalVariation = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbalVariation->select('material.id AS material');
        $dbalVariation->addSelect('material.event');
        $dbalVariation->addSelect('offer.id AS offer');
        $dbalVariation->addSelect('offer.const AS offer_const');
        $dbalVariation->addSelect('variation.id AS variation');
        $dbalVariation->addSelect('variation.const AS variation_const');
        $dbalVariation->addSelect('NULL::uuid AS modification');
        $dbalVariation->addSelect('NULL::uuid AS modification_const');

        $dbalVariation
            ->from(MaterialVariation::class, 'variation')
            ->where('variation.article = :article');

        $dbalVariation
            ->join(
                'variation',
                MaterialOffer::class, 'offer',
                'offer.id = variation.offer'
            );

        $dbalVariation
            ->join(
                'offer',
                Material::class, 'material',
                'material.event = offer.event'
            );


        /** Поиск артикула MODIFICATION */

        $dbalModification = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbalModification->select('material.id AS material');
        $dbalModification->addSelect('material.event');
        $dbalModification->addSelect('offer.id AS offer');
        $dbalModification->addSelect('offer.const AS offer_const');
        $dbalModification->addSelect('variation.id AS variation');
        $dbalModification->addSelect('variation.const AS variation_const');
        $dbalModification->addSelect('modification.id  AS modification');
        $dbalModification->addSelect('modification.const  AS modification_const');


        $dbalModification
            ->from(MaterialModification::class, 'modification')
            ->where('modification.article = :article');


        $dbalModification
            ->join(
                'modification',
                MaterialVariation::class, 'variation',
                'variation.id = modification.variation'
            );


        $dbalModification
            ->join(
                'variation',
                MaterialOffer::class, 'offer',
                'offer.id = variation.offer'
            );

        $dbalModification
            ->join(
                'offer',
                Material::class, 'material',
                'material.event = offer.event'
            );


        /** UNION */

        $union = [
            str_replace('SELECT', '', $dbalInfo->getSQL()),
            $dbalOffer->getSQL(),
            $dbalVariation->getSQL(),
            $dbalModification->getSQL()
        ];

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);
        $dbal->select(implode(' UNION ', $union));
        $dbal->setParameter('article', $article);

        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchHydrate(CurrentMaterialDTO::class);

    }


    public function oldFind(string $article): CurrentMaterialDTO|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(Material::class, 'material');

        $dbal->leftJoin(
            'material',
            MaterialInfo::class,
            'info',
            'info.material = material'
        );

        $dbal->leftJoin(
            'material',
            MaterialOffer::class,
            'offer',
            'offer.event = material.event'
        );

        $dbal->leftJoin(
            'offer',
            MaterialVariation::class,
            'variation',
            'variation.offer = offer.id'
        );

        $dbal->leftJoin(
            'variation',
            MaterialModification::class,
            'modification',
            'modification.variation = variation.id'
        );

        $dbal->where('info.article = :article');
        $dbal->orWhere('offer.article = :article');
        $dbal->orWhere('variation.article = :article');
        $dbal->orWhere('modification.article = :article');

        $dbal->setParameter('article', $article);


        $dbal->select('material.id AS material');
        $dbal->addSelect('material.event');

        /** Торговое предложение */
        $dbal->addSelect('offer.id AS offer');
        $dbal->addSelect('offer.const AS offer_const');

        /** Множественный вариант торгового предложения */
        $dbal->addSelect('variation.id AS variation');
        $dbal->addSelect('variation.const AS variation_const');

        /** Модификация множественного варианта торгового предложения */
        $dbal->addSelect('modification.id AS modification');
        $dbal->addSelect('modification.const AS modification_const');


        return $dbal
            //->enableCache('materials-catalog', 86400)
            ->fetchHydrate(CurrentMaterialDTO::class);
    }
}
