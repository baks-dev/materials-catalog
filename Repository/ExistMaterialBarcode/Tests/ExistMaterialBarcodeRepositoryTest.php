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
 */

declare(strict_types=1);

namespace BaksDev\Materials\Catalog\Repository\ExistMaterialBarcode;

use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('materials-catalog')]
#[Group('materials-catalog-repository')]
#[When(env: 'test')]
final class ExistMaterialBarcodeRepositoryTest extends KernelTestCase
{
    public function testExists(): void
    {
        $ExistMaterialBarcodeRepository = self::getContainer()->get(ExistMaterialBarcodeInterface::class);

        /** @var ExistMaterialBarcodeRepository $ExistMaterialBarcodeRepository */
        $result = $ExistMaterialBarcodeRepository
            ->forBarcode(new MaterialBarcode('04650198060810'))
            ->forMaterial(new MaterialUid('019a39ac-7bcd-7e65-a8d4-3e9e04accb51'))
            ->forOffer(new MaterialOfferConst('019aedc7-0ff1-7393-bae9-bb41a6d8c681'))
            ->forVariation(new MaterialVariationConst('019aedc7-1002-7ff2-a57c-00484f5284ac'))
            ->forModification(new MaterialModificationConst('0193d94c-2ff9-7296-8348-fce652bc2bda'))
            ->exist();


        self::assertTrue(true);
    }
}