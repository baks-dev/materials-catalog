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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Tests;


use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Repository\CurrentProductEvent\CurrentProductEventInterface;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Active\ActiveDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\CategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Description\ProductDescriptionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Info\InfoDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialHandler;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\ProductOffersCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\ProductModificationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\ProductVariationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\PhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price\PriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Seo\SeoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Trans\MaterialTransDTO;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Category\Type\Offers\Id\CategoryProductOffersUid;
use BaksDev\Products\Category\Type\Offers\Modification\CategoryProductModificationUid;
use BaksDev\Products\Category\Type\Offers\Variation\CategoryProductVariationUid;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group materials-catalog
 * @group materials-catalog-usecase
 *
 * @depends BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Tests\ProductsProductNewTest::class
 */
#[When(env: 'test')]
class ProductsProductEditTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var CurrentProductEventInterface $CurrentProductEvent */
        $CurrentProductEvent = self::getContainer()->get(CurrentProductEventInterface::class);
        $ProductEvent = $CurrentProductEvent->findByProduct(MaterialUid::TEST);

        self::assertNotNull($ProductEvent);

        $ProductDTO = new MaterialDTO();
        /** @var MaterialDTO $ProductDTO */
        $ProductDTO = $ProductEvent->getDto($ProductDTO);


        /** CategoryCollectionDTO */

        $CategoryCollectionDTO = $ProductDTO->getCategory();

        /** @var CategoryCollectionDTO $Category */
        foreach($CategoryCollectionDTO as $Category)
        {
            self::assertEquals(CategoryProductUid::TEST, $Category->getCategory());

            self::assertTrue($Category->getRoot());
            $Category->setRoot(false);
            self::assertFalse($Category->getRoot());
        }

        /** SeoCollectionDTO */

        $SeoCollection = $ProductDTO->getSeo();

        /** @var SeoCollectionDTO $SeoCollectionDTO */
        foreach($SeoCollection as $SeoCollectionDTO)
        {
            self::assertSame('Test New Title', $SeoCollectionDTO->getTitle());
            $SeoCollectionDTO->setTitle('Test Edit Title');
            self::assertSame('Test Edit Title', $SeoCollectionDTO->getTitle());

            self::assertSame('Test New Keywords', $SeoCollectionDTO->getKeywords());
            $SeoCollectionDTO->setKeywords('Test Edit Keywords');
            self::assertSame('Test Edit Keywords', $SeoCollectionDTO->getKeywords());

            self::assertSame('Test New Description', $SeoCollectionDTO->getDescription());
            $SeoCollectionDTO->setDescription('Test Edit Description');
            self::assertSame('Test Edit Description', $SeoCollectionDTO->getDescription());
        }


        /** PhotoCollectionDTO */

        $PhotoCollection = $ProductDTO->getPhoto();
        self::assertCount(1, $PhotoCollection);

        /** @var PhotoCollectionDTO $PhotoCollectionDTO */
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

        foreach($ProductDTO->getProperty() as $PropertyCollectionDTO)
        {
            self::assertEquals(CategoryProductSectionFieldUid::TEST, $PropertyCollectionDTO->getField());

            self::assertSame('Test New Property Value', $PropertyCollectionDTO->getValue());
            $PropertyCollectionDTO->setValue('Test Edit Property Value');
            self::assertSame('Test Edit Property Value', $PropertyCollectionDTO->getValue());
        }


        /** ProductDescriptionDTO */

        $productDescription = $ProductDTO->getDescription();

        /** @var ProductDescriptionDTO $productDescriptionDto */
        foreach($productDescription as $productDescriptionDto)
        {
            self::assertSame('Test New Description', $productDescriptionDto->getDescription());
            $productDescriptionDto->setDescription('Test Edit Description');
            self::assertSame('Test Edit Description', $productDescriptionDto->getDescription());

            self::assertSame('Test New Preview', $productDescriptionDto->getPreview());
            $productDescriptionDto->setPreview('Test Edit Preview');
            self::assertSame('Test Edit Preview', $productDescriptionDto->getPreview());
        }

        /** InfoDTO */

        /** @var InfoDTO $InfoDTO */
        $InfoDTO = $ProductDTO->getInfo();

        self::assertSame('Test New Info Article', $InfoDTO->getArticle());
        $InfoDTO->setArticle('Test Edit Info Article');
        self::assertSame('Test Edit Info Article', $InfoDTO->getArticle());

        self::assertSame(5, $InfoDTO->getSort());
        $InfoDTO->setSort(25);
        self::assertSame(25, $InfoDTO->getSort());

        self::assertSame('test_new_info_url', $InfoDTO->getUrl());
        $InfoDTO->setUrl('test_edit_info_url');
        self::assertSame('test_edit_info_url', $InfoDTO->getUrl());

        self::assertTrue($InfoDTO->getProfile()->equals(UserProfileUid::TEST));


        /** PriceDTO */

        /** @var PriceDTO $PriceDTO */
        $PriceDTO = $ProductDTO->getPrice();

        self::assertSame(50.0, $PriceDTO->getPrice()->getValue());
        $PriceMoney = new Money(56.0);
        $PriceDTO->setPrice($PriceMoney);
        self::assertSame($PriceMoney, $PriceDTO->getPrice());

        self::assertTrue($PriceDTO->getCurrency()->equals(Currency::TEST));

        self::assertTrue($PriceDTO->getRequest());
        $PriceDTO->setRequest(false);
        self::assertFalse($PriceDTO->getRequest());


        /** ActiveDTO */


        /** @var  ActiveDTO $ActiveDTO */
        $ActiveDTO = $ProductDTO->getActive();


        $activeTestDateNew = new DateTimeImmutable('2024-09-15 15:12:00');
        $activeTestDateEdit = new DateTimeImmutable('2024-09-17 10:00:00');

        self::assertFalse($ActiveDTO->getActive());
        $ActiveDTO->setActive(true);
        self::assertTrue($ActiveDTO->getActive());


        // Active From Date

        self::assertEquals(
            $activeTestDateNew->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveFrom()->format('Y-m-d H:i:s')
        );
        $ActiveDTO->setActiveFrom($activeTestDateEdit);
        self::assertSame(
            $activeTestDateEdit->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveFrom()->format('Y-m-d H:i:s')
        );


        // Active From Time
        // Время присваивается из даты, новое время != New, === Edit

        self::assertEquals(
            $activeTestDateEdit->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveFromTime()->format('Y-m-d H:i:s')
        );
        $ActiveDTO->setActiveFromTime($activeTestDateEdit);
        self::assertSame(
            $activeTestDateEdit->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveFromTime()->format('Y-m-d H:i:s')
        );

        //  Active To Date

        self::assertEquals(
            $activeTestDateNew->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveTo()->format('Y-m-d H:i:s')
        );
        $ActiveDTO->setActiveTo($activeTestDateEdit);
        self::assertSame(
            $activeTestDateEdit->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveTo()->format('Y-m-d H:i:s')
        );

        //  Active To Time
        // Время присваивается из даты, новое время != New, === Edit

        self::assertEquals(
            $activeTestDateEdit->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveToTime()->format('Y-m-d H:i:s')
        );
        $ActiveDTO->setActiveToTime($activeTestDateEdit);
        self::assertSame(
            $activeTestDateEdit->format('Y-m-d H:i:s'),
            $ActiveDTO->getActiveToTime()->format('Y-m-d H:i:s')
        );


        /** ProductTransDTO */

        $ProductTrans = $ProductDTO->getTranslate();

        /** @var MaterialTransDTO $ProductTransDTO */
        foreach($ProductTrans as $ProductTransDTO)
        {
            self::assertSame('RU_ru', $ProductTransDTO->getName());

            $ProductTransDTO->setName('EN_en');
            self::assertSame('EN_en', $ProductTransDTO->getName());
        }


        ///////////////////////////////////////////
        ////////////////// Offer //////////////////
        ///////////////////////////////////////////

        /** ProductOffersCollectionDTO */

        /** @var ProductOffersCollectionDTO $ProductOffersCollectionDTO */
        $ProductOffersCollection = $ProductDTO->getOffer();

        foreach($ProductOffersCollection as $ProductOffersCollectionDTO)
        {
            self::assertSame('Test New Offer Value', $ProductOffersCollectionDTO->getValue());
            $ProductOffersCollectionDTO->setValue('Test Edit Offer Value');
            self::assertSame('Test Edit Offer Value', $ProductOffersCollectionDTO->getValue());

            self::assertSame('Test New Offer Postfix', $ProductOffersCollectionDTO->getPostfix());
            $ProductOffersCollectionDTO->setPostfix('Test Edit Offer Postfix');
            self::assertSame('Test Edit Offer Postfix', $ProductOffersCollectionDTO->getPostfix());

            $ProductOfferPriceDTO = $ProductOffersCollectionDTO->getPrice();
            self::assertTrue($ProductOfferPriceDTO->getPrice()->equals(55.5));

            $ModificationPriceMoney = new Money(50.5);
            $ProductOfferPriceDTO->setPrice($ModificationPriceMoney);
            self::assertSame($ModificationPriceMoney, $ProductOfferPriceDTO->getPrice());

            self::assertTrue($ProductOfferPriceDTO->getCurrency()->equals(Currency::TEST));

            self::assertTrue(
                $ProductOffersCollectionDTO
                    ->getCategoryOffer()
                    ->equals(CategoryProductOffersUid::TEST)
            );

            self::assertSame('Test New Offer Article', $ProductOffersCollectionDTO->getArticle());
            $ProductOffersCollectionDTO->setArticle('Test Edit Offer Article');
            self::assertSame('Test Edit Offer Article', $ProductOffersCollectionDTO->getArticle());

            self::assertTrue($ProductOffersCollectionDTO->getConst()->equals(ProductOfferConst::TEST));


            ///////////////////////////////////////////
            ///////////////// Variation ///////////////
            ///////////////////////////////////////////

            /** ProductOffersVariationCollectionDTO */

            $ProductOffersVariationCollection = $ProductOffersCollectionDTO->getVariation();

            /** @var ProductVariationCollectionDTO $ProductOffersVariationCollectionDTO */
            foreach($ProductOffersVariationCollection as $ProductOffersVariationCollectionDTO)
            {
                $ProductVariationPriceDTO = $ProductOffersVariationCollectionDTO->getPrice();
                self::assertTrue($ProductVariationPriceDTO->getPrice()->equals(55.0));

                $ModificationPriceMoney = new Money(75.0);
                self::assertSame(75.0, $ModificationPriceMoney->getValue());

                $ProductVariationPriceDTO->setPrice($ModificationPriceMoney);
                self::assertSame($ModificationPriceMoney, $ProductVariationPriceDTO->getPrice());

                self::assertTrue(
                    $ProductVariationPriceDTO
                        ->getCurrency()
                        ->equals(Currency::TEST)
                );

                self::assertTrue(
                    $ProductOffersVariationCollectionDTO
                        ->getConst()
                        ->equals(ProductVariationConst::TEST)
                );

                self::assertSame(
                    'Test New Variation Article',
                    $ProductOffersVariationCollectionDTO->getArticle()
                );

                $ProductOffersVariationCollectionDTO->setArticle('Test Edit Variation Article');
                self::assertSame(
                    'Test Edit Variation Article',
                    $ProductOffersVariationCollectionDTO->getArticle()
                );

                self::assertSame(
                    'Test New Variation Value',
                    $ProductOffersVariationCollectionDTO->getValue()
                );

                $ProductOffersVariationCollectionDTO->setValue('Test Edit Variation Value');
                self::assertSame(
                    'Test Edit Variation Value',
                    $ProductOffersVariationCollectionDTO->getValue()
                );

                self::assertSame(
                    'Test New Variation Postfix',
                    $ProductOffersVariationCollectionDTO->getPostfix()
                );

                $ProductOffersVariationCollectionDTO->setPostfix('Test Edit Variation Postfix');
                self::assertSame(
                    'Test Edit Variation Postfix',
                    $ProductOffersVariationCollectionDTO->getPostfix()
                );

                self::assertTrue(
                    $ProductOffersVariationCollectionDTO
                        ->getCategoryVariation()
                        ->equals(CategoryProductVariationUid::TEST)
                );


                ///////////////////////////////////////////
                ////////////// Modification ///////////////
                ///////////////////////////////////////////

                /** ProductOffersVariationModificationCollectionDTO */

                /** @var ProductModificationCollectionDTO $ProductOffersVariationModificationCollectionDTO */
                $ProductOffersVariationModificationCollection = $ProductOffersVariationCollectionDTO->getModification();

                foreach($ProductOffersVariationModificationCollection as $ProductOffersVariationModificationCollectionDTO)
                {
                    $ProductModificationPriceDTO = $ProductOffersVariationModificationCollectionDTO->getPrice();

                    self::assertTrue(
                        $ProductModificationPriceDTO
                            ->getPrice()
                            ->equals(65.0)
                    );

                    $ModificationPriceMoney = new Money(50.5);
                    self::assertSame(50.5, $ModificationPriceMoney->getValue());

                    $ProductModificationPriceDTO->setPrice($ModificationPriceMoney);
                    self::assertSame($ModificationPriceMoney, $ProductModificationPriceDTO->getPrice());

                    $ProductModificationPriceDTO->getCurrency()->equals(Currency::TEST);

                    $ProductOffersVariationModificationCollectionDTO->setPrice($ProductModificationPriceDTO);
                    self::assertSame(
                        $ProductModificationPriceDTO,
                        $ProductOffersVariationModificationCollectionDTO->getPrice()
                    );

                    self::assertTrue(
                        $ProductOffersVariationModificationCollectionDTO
                            ->getConst()
                            ->equals(ProductModificationConst::TEST)
                    );

                    self::assertSame(
                        'Test New Modification Article',
                        $ProductOffersVariationModificationCollectionDTO->getArticle()
                    );

                    $ProductOffersVariationModificationCollectionDTO->setArticle('Test Edit Modification Article');
                    self::assertSame(
                        'Test Edit Modification Article',
                        $ProductOffersVariationModificationCollectionDTO->getArticle()
                    );

                    self::assertSame(
                        'Test New Modification Value',
                        $ProductOffersVariationModificationCollectionDTO->getValue()
                    );

                    $ProductOffersVariationModificationCollectionDTO->setValue('Test Edit Modification Value');
                    self::assertSame(
                        'Test Edit Modification Value',
                        $ProductOffersVariationModificationCollectionDTO->getValue()
                    );

                    self::assertSame(
                        'Test New Modification Postfix',
                        $ProductOffersVariationModificationCollectionDTO->getPostfix()
                    );

                    $ProductOffersVariationModificationCollectionDTO->setPostfix('Test Edit Modification Postfix');
                    self::assertSame(
                        'Test Edit Modification Postfix',
                        $ProductOffersVariationModificationCollectionDTO->getPostfix()
                    );

                    self::assertTrue(
                        $ProductOffersVariationModificationCollectionDTO
                            ->getCategoryModification()
                            ->equals(CategoryProductModificationUid::TEST)
                    );
                }
            }
        }


        /** @var MaterialHandler $ProductHandler */
        $ProductHandler = self::getContainer()->get(MaterialHandler::class);
        $handle = $ProductHandler->handle($ProductDTO);

        self::assertTrue($handle instanceof Material);

    }

}
