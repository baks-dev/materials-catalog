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

namespace BaksDev\Materials\Catalog\Repository\CurrentMaterialByArticle\Tests;

use BaksDev\Materials\Catalog\Repository\CurrentMaterialByArticle\CurrentMaterialDTO;
use BaksDev\Materials\Catalog\Repository\CurrentMaterialByArticle\MaterialConstByArticleInterface;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('materials-catalog')]
class MaterialConstByArticleTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        /** @var MaterialConstByArticleInterface $MaterialConstByArticle */
        $MaterialConstByArticle = self::getContainer()->get(MaterialConstByArticleInterface::class);

        $CurrentMaterialDTO = $MaterialConstByArticle->find('LS-W-WHITE-XS');

        self::assertNotFalse($CurrentMaterialDTO);
        self::assertInstanceOf(CurrentMaterialDTO::class, $CurrentMaterialDTO);
        self::assertInstanceOf(MaterialUid::class, $CurrentMaterialDTO->getMaterial());
        self::assertInstanceOf(MaterialEventUid::class, $CurrentMaterialDTO->getEvent());

        /**
         * MaterialOffer
         */

        $CurrentMaterialDTO->getOffer() ?
            self::assertInstanceOf(MaterialOfferUid::class, $CurrentMaterialDTO->getOffer()) :
            self::assertNull($CurrentMaterialDTO->getOffer());

        $CurrentMaterialDTO->getOfferConst() ?
            self::assertInstanceOf(MaterialOfferConst::class, $CurrentMaterialDTO->getOfferConst()) :
            self::assertNull($CurrentMaterialDTO->getOfferConst());

        /**
         * MaterialVariation
         */

        $CurrentMaterialDTO->getVariation() ?
            self::assertInstanceOf(MaterialVariationUid::class, $CurrentMaterialDTO->getVariation()) :
            self::assertNull($CurrentMaterialDTO->getVariation());

        $CurrentMaterialDTO->getVariationConst() ?
            self::assertInstanceOf(MaterialVariationConst::class, $CurrentMaterialDTO->getVariationConst()) :
            self::assertNull($CurrentMaterialDTO->getVariationConst());

        /**
         * MaterialModification
         */

        $CurrentMaterialDTO->getModification() ?
            self::assertInstanceOf(MaterialModificationUid::class, $CurrentMaterialDTO->getModification()) :
            self::assertNull($CurrentMaterialDTO->getModification());

        $CurrentMaterialDTO->getModificationConst() ?
            self::assertInstanceOf(MaterialModificationConst::class, $CurrentMaterialDTO->getModificationConst()) :
            self::assertNull($CurrentMaterialDTO->getModificationConst());


    }
}