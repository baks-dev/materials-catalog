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

use BaksDev\Materials\Catalog\Repository\CurrentMaterialByArticle\MaterialConstByArticleInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group materials-catalog
 */
#[When(env: 'test')]
class MaterialConstByArticleTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        /** @var MaterialConstByArticleInterface $MaterialConstByArticle */
        $MaterialConstByArticle = self::getContainer()->get(MaterialConstByArticleInterface::class);

        $CurrentMaterialDTO = $MaterialConstByArticle->find('TH202-16-195-45-84W');
        self::assertNotFalse($CurrentMaterialDTO);

        /*dd(sprintf(
            'https://bundles.baks.dev/admin/material/edit/%s?offfer=%s&variation=%s&modification=%s',
            $CurrentMaterialDTO->getEvent(),
            $CurrentMaterialDTO->getOfferConst(),
            $CurrentMaterialDTO->getVariationConst(),
            $CurrentMaterialDTO->getModificationConst()
        ));*/

    }


}