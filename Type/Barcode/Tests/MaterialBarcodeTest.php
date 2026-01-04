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

namespace BaksDev\Materials\Catalog\Type\Barcode\Tests;

use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Uid\UuidV7;

#[When(env: 'test')]
#[Group('materials-catalog')]
final class MaterialBarcodeTest extends TestCase
{
    public function testBarcodeGeneration(): void
    {
        $barcode = new MaterialBarcode();
        $this->assertMatchesRegularExpression('/^\d{13}$/', $barcode->getValue());

        $customValue = '0123456789012';
        $barcode = new MaterialBarcode($customValue);
        $this->assertEquals($customValue, $barcode->getValue());
    }


    public function testUidBarcodeEncoding()
    {
        $count = 0;

        $barcodes = null;

        while(true)
        {
            $barcode = MaterialBarcode::generate();
            self::assertFalse(isset($barcodes[$barcode]), $barcode.' - '.$count);

            $barcodes[$barcode] = true;

            $count++;

            if($count > 100)
            {
                break;
            }
        }
    }

    public function testRandomGeneration()
    {
        $barcode = MaterialBarcode::generate(new UuidV7());
        self::assertNotEmpty($barcode);

        $barcodeNull = MaterialBarcode::generate();
        self::assertNotEmpty($barcodeNull);

        $barcodeString = MaterialBarcode::generate('35eca90b-5331-728a-9222-d051a59a7941');
        $this->assertEquals('2929056464568', $barcodeString);

        $barcodeUid = MaterialBarcode::generate(new MaterialUid(MaterialUid::TEST));
        $this->assertEquals('2686472941966', $barcodeUid);

    }

    public function testBarcodeEncoding(): void
    {
        $article = 'TestArticle';
        $expected = '4601895645651';

        $barcode = new MaterialBarcode();
        $result = $barcode->barcode($article);

        $this->assertEquals($expected, $result);
    }


}
