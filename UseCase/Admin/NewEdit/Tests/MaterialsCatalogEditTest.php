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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Tests;

use BaksDev\Materials\Catalog\Controller\Admin\Tests\DeleteAdminControllerTest;
use BaksDev\Materials\Catalog\Controller\Admin\Tests\EditAdminControllerTest;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Repository\CurrentMaterialEvent\CurrentMaterialEventInterface;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\MaterialCategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialHandler;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\MaterialOffersCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\MaterialVariationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\MaterialModificationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\MaterialPhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price\MaterialPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Trans\MaterialTransDTO;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Materials\Category\Type\Offers\Id\CategoryMaterialOffersUid;
use BaksDev\Materials\Category\Type\Offers\Modification\CategoryMaterialModificationUid;
use BaksDev\Materials\Category\Type\Offers\Variation\CategoryMaterialVariationUid;
use BaksDev\Materials\Category\Type\Section\Field\Id\CategoryMaterialSectionFieldUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


#[When(env: 'test')]
#[Group('materials-catalog')]
class MaterialsCatalogEditTest extends KernelTestCase
{
    #[DependsOnClass(MaterialsCatalogNewTest::class)]
    #[DependsOnClass(EditAdminControllerTest::class)]
    #[DependsOnClass(DeleteAdminControllerTest::class)]
    public function testUseCase(): void
    {
        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var CurrentMaterialEventInterface $CurrentMaterialEvent */
        $CurrentMaterialEvent = self::getContainer()->get(CurrentMaterialEventInterface::class);
        $MaterialEvent = $CurrentMaterialEvent->findByMaterial(MaterialUid::TEST);

        self::assertNotNull($MaterialEvent);

        $MaterialDTO = new MaterialDTO();
        /** @var MaterialDTO $MaterialDTO */
        $MaterialDTO = $MaterialEvent->getDto($MaterialDTO);


        /** CategoryCollectionDTO */

        $CategoryCollectionDTO = $MaterialDTO->getCategory();

        /** @var MaterialCategoryCollectionDTO $Category */
        foreach($CategoryCollectionDTO as $Category)
        {
            self::assertEquals(CategoryMaterialUid::TEST, $Category->getCategory());

            self::assertTrue($Category->getRoot());
            $Category->setRoot(false);
            self::assertFalse($Category->getRoot());
        }


        /** PhotoCollectionDTO */

        $PhotoCollection = $MaterialDTO->getPhoto();
        self::assertCount(1, $PhotoCollection);

        /** @var MaterialPhotoCollectionDTO $PhotoCollectionDTO */
        foreach($PhotoCollection as $PhotoCollectionDTO)
        {
            self::assertNotEmpty($PhotoCollectionDTO->getName());
            self::assertNotEmpty($PhotoCollectionDTO->getExt());
            self::assertNotEmpty($PhotoCollectionDTO->getSize());
            self::assertTrue($PhotoCollectionDTO->getRoot());

            $PhotoCollectionDTO->setRoot(false);
            self::assertFalse($PhotoCollectionDTO->getRoot());
        }


        /** PropertyCollectionDTO */

        /*foreach($MaterialDTO->getProperty() as $PropertyCollectionDTO)
        {
            self::assertEquals(CategoryMaterialSectionFieldUid::TEST, $PropertyCollectionDTO->getField());

            self::assertSame('Test New Property Value', $PropertyCollectionDTO->getValue());
            $PropertyCollectionDTO->setValue('Test Edit Property Value');
            self::assertSame('Test Edit Property Value', $PropertyCollectionDTO->getValue());
        }*/


        /** PriceDTO */

        /** @var MaterialPriceDTO $PriceDTO */
        $PriceDTO = $MaterialDTO->getPrice();

        self::assertSame(50.0, $PriceDTO->getPrice()->getValue());
        $PriceMoney = new Money(56.0);
        $PriceDTO->setPrice($PriceMoney);
        self::assertSame($PriceMoney, $PriceDTO->getPrice());

        self::assertTrue($PriceDTO->getCurrency()->equals(Currency::TEST));

        //        self::assertTrue($PriceDTO->getRequest());
        //        $PriceDTO->setRequest(false);
        //        self::assertFalse($PriceDTO->getRequest());


        /** MaterialTransDTO */

        $MaterialTrans = $MaterialDTO->getTranslate();

        /** @var MaterialTransDTO $MaterialTransDTO */
        foreach($MaterialTrans as $MaterialTransDTO)
        {
            self::assertSame('RU_ru', $MaterialTransDTO->getName());

            $MaterialTransDTO->setName('EN_en');
            self::assertSame('EN_en', $MaterialTransDTO->getName());
        }


        ///////////////////////////////////////////
        ////////////////// Offer //////////////////
        ///////////////////////////////////////////

        /** MaterialOffersCollectionDTO */

        /** @var MaterialOffersCollectionDTO $MaterialOffersCollectionDTO */
        $MaterialOffersCollection = $MaterialDTO->getOffer();

        foreach($MaterialOffersCollection as $MaterialOffersCollectionDTO)
        {
            self::assertSame('100', $MaterialOffersCollectionDTO->getValue());
            $MaterialOffersCollectionDTO->setValue('Test Edit Offer Value');
            self::assertSame('Test Edit Offer Value', $MaterialOffersCollectionDTO->getValue());


            $MaterialOfferPriceDTO = $MaterialOffersCollectionDTO->getPrice();
            self::assertTrue($MaterialOfferPriceDTO->getPrice()->equals(55.5));

            $ModificationPriceMoney = new Money(50.5);
            $MaterialOfferPriceDTO->setPrice($ModificationPriceMoney);
            self::assertSame($ModificationPriceMoney, $MaterialOfferPriceDTO->getPrice());

            self::assertTrue($MaterialOfferPriceDTO->getCurrency()->equals(Currency::TEST));

            self::assertTrue(
                $MaterialOffersCollectionDTO
                    ->getCategoryOffer()
                    ->equals(CategoryMaterialOffersUid::TEST),
            );

            self::assertSame('Test New Offer Article', $MaterialOffersCollectionDTO->getArticle());
            $MaterialOffersCollectionDTO->setArticle('Test Edit Offer Article');
            self::assertSame('Test Edit Offer Article', $MaterialOffersCollectionDTO->getArticle());

            self::assertTrue($MaterialOffersCollectionDTO->getConst()->equals(MaterialOfferConst::TEST));


            ///////////////////////////////////////////
            ///////////////// Variation ///////////////
            ///////////////////////////////////////////

            /** MaterialOffersVariationCollectionDTO */

            $MaterialOffersVariationCollection = $MaterialOffersCollectionDTO->getVariation();

            /** @var MaterialVariationCollectionDTO $MaterialOffersVariationCollectionDTO */
            foreach($MaterialOffersVariationCollection as $MaterialOffersVariationCollectionDTO)
            {
                $MaterialVariationPriceDTO = $MaterialOffersVariationCollectionDTO->getPrice();
                self::assertTrue($MaterialVariationPriceDTO->getPrice()->equals(55.0));

                $ModificationPriceMoney = new Money(75.0);
                self::assertSame(75.0, $ModificationPriceMoney->getValue());

                $MaterialVariationPriceDTO->setPrice($ModificationPriceMoney);
                self::assertSame($ModificationPriceMoney, $MaterialVariationPriceDTO->getPrice());

                self::assertTrue(
                    $MaterialVariationPriceDTO
                        ->getCurrency()
                        ->equals(Currency::TEST),
                );

                self::assertTrue(
                    $MaterialOffersVariationCollectionDTO
                        ->getConst()
                        ->equals(MaterialVariationConst::TEST),
                );

                self::assertSame(
                    'Test New Variation Article',
                    $MaterialOffersVariationCollectionDTO->getArticle(),
                );

                $MaterialOffersVariationCollectionDTO->setArticle('Test Edit Variation Article');
                self::assertSame(
                    'Test Edit Variation Article',
                    $MaterialOffersVariationCollectionDTO->getArticle(),
                );

                self::assertSame(
                    '200',
                    $MaterialOffersVariationCollectionDTO->getValue(),
                );

                $MaterialOffersVariationCollectionDTO->setValue('Test Edit Variation Value');
                self::assertSame(
                    'Test Edit Variation Value',
                    $MaterialOffersVariationCollectionDTO->getValue(),
                );

                self::assertTrue(
                    $MaterialOffersVariationCollectionDTO
                        ->getCategoryVariation()
                        ->equals(CategoryMaterialVariationUid::TEST),
                );


                ///////////////////////////////////////////
                ////////////// Modification ///////////////
                ///////////////////////////////////////////

                /** MaterialOffersVariationModificationCollectionDTO */

                /** @var MaterialModificationCollectionDTO $MaterialOffersVariationModificationCollectionDTO */
                $MaterialOffersVariationModificationCollection = $MaterialOffersVariationCollectionDTO->getModification();

                foreach($MaterialOffersVariationModificationCollection as $MaterialOffersVariationModificationCollectionDTO)
                {
                    $MaterialModificationPriceDTO = $MaterialOffersVariationModificationCollectionDTO->getPrice();

                    self::assertTrue(
                        $MaterialModificationPriceDTO
                            ->getPrice()
                            ->equals(65.0),
                    );

                    $ModificationPriceMoney = new Money(50.5);
                    self::assertSame(50.5, $ModificationPriceMoney->getValue());

                    $MaterialModificationPriceDTO->setPrice($ModificationPriceMoney);
                    self::assertSame($ModificationPriceMoney, $MaterialModificationPriceDTO->getPrice());

                    $MaterialModificationPriceDTO->getCurrency()->equals(Currency::TEST);

                    $MaterialOffersVariationModificationCollectionDTO->setPrice($MaterialModificationPriceDTO);
                    self::assertSame(
                        $MaterialModificationPriceDTO,
                        $MaterialOffersVariationModificationCollectionDTO->getPrice(),
                    );

                    self::assertTrue(
                        $MaterialOffersVariationModificationCollectionDTO
                            ->getConst()
                            ->equals(MaterialModificationConst::TEST),
                    );

                    self::assertSame(
                        'Test New Modification Article',
                        $MaterialOffersVariationModificationCollectionDTO->getArticle(),
                    );

                    $MaterialOffersVariationModificationCollectionDTO->setArticle('Test Edit Modification Article');
                    self::assertSame(
                        'Test Edit Modification Article',
                        $MaterialOffersVariationModificationCollectionDTO->getArticle(),
                    );

                    self::assertSame(
                        '300',
                        $MaterialOffersVariationModificationCollectionDTO->getValue(),
                    );

                    $MaterialOffersVariationModificationCollectionDTO->setValue('Test Edit Modification Value');
                    self::assertSame(
                        'Test Edit Modification Value',
                        $MaterialOffersVariationModificationCollectionDTO->getValue(),
                    );

                    self::assertTrue(
                        $MaterialOffersVariationModificationCollectionDTO
                            ->getCategoryModification()
                            ->equals(CategoryMaterialModificationUid::TEST),
                    );
                }
            }
        }


        /** @var MaterialHandler $MaterialHandler */
        $MaterialHandler = self::getContainer()->get(MaterialHandler::class);
        $handle = $MaterialHandler->handle($MaterialDTO);

        self::assertTrue($handle instanceof Material);

    }

}
