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

namespace BaksDev\Materials\Catalog\UseCase\Admin\Invariable\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\MaterialInvariable;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Invariable\MaterialInvariableUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\UseCase\Admin\Invariable\MaterialInvariableDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\Invariable\MaterialInvariableHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group materials-catalog
 */
#[When(env: 'test')]
class MaterialInvariableTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(MaterialInvariable::class)
            ->findOneBy(['id' => MaterialInvariableUid::TEST]);

        if($main)
        {
            $em->remove($main);
        }


        $em->flush();
        $em->clear();
    }


    public function testUseCase(): void
    {
        /** @see MaterialInvariableDTO */
        $MaterialInvariableDTO = new MaterialInvariableDTO();

        $MaterialInvariableDTO
            ->setMaterial(new MaterialUid(MaterialUid::TEST))
            ->setOffer(new MaterialOfferConst(MaterialOfferConst::TEST))
            ->setVariation(new MaterialVariationConst(MaterialVariationConst::TEST))
            ->setModification(new MaterialModificationConst(MaterialModificationConst::TEST));


        /** @var MaterialInvariableHandler $MaterialInvariableHandler */
        $MaterialInvariableHandler = self::getContainer()->get(MaterialInvariableHandler::class);
        $handle = $MaterialInvariableHandler->handle($MaterialInvariableDTO);

        self::assertTrue(($handle instanceof MaterialInvariable), $handle.': Ошибка MaterialInvariable');

    }
}