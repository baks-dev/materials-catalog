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

namespace BaksDev\Materials\Catalog\Repository\MaterialsChoice;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Products\Product\Repository\MaterialsChoice\MaterialsChoiceInterface;
use Generator;

final readonly class MaterialsChoiceRepository implements MaterialsChoiceInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает все идентификаторы сырья (MaterialUid) с названием указанной категории
     */
    public function findAll(CategoryMaterialUid|false $category = false): Generator|false
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->from(Material::class, 'material');

        $qb->join(
            'material',
            MaterialInfo::class,
            'info',
            'info.material = material.id'
        );

        if($category)
        {
            $qb
                ->join(
                    'material',
                    MaterialCategory::class,
                    'category',
                    'category.event = material.event AND category.category = :category'
                )
                ->setParameter(
                    'category',
                    $category,
                    CategoryMaterialUid::TYPE
                );
        }


        $qb->join(
            'material',
            MaterialTrans::class,
            'trans',
            'trans.event = material.event AND trans.local = :local'
        );

        $qb->addSelect('material.id AS value');
        $qb->addSelect('trans.name AS attr');
        $qb->addSelect('info.article AS option');

        $qb->orderBy('trans.name');

        return $qb
            ->enableCache('materials-catalog', 86400)
            ->fetchAllHydrate(MaterialUid::class);

    }
}