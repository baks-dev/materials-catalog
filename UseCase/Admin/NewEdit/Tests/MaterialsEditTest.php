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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Tests;


use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Repository\CurrentMaterialEvent\CurrentMaterialEventInterface;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Active\ActiveDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\MaterialCategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Description\MaterialDescriptionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Info\MaterialInfoDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialHandler;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\MaterialOffersCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\MaterialVariationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\MaterialModificationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\MaterialPhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price\MaterialPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Seo\MaterialSeoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Trans\MaterialTransDTO;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Materials\Category\Type\Offers\Id\CategoryMaterialOffersUid;
use BaksDev\Materials\Category\Type\Offers\Modification\CategoryMaterialModificationUid;
use BaksDev\Materials\Category\Type\Offers\Variation\CategoryMaterialVariationUid;
use BaksDev\Materials\Category\Type\Section\Field\Id\CategoryMaterialSectionFieldUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;
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
 * @depends BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Tests\MaterialMaterialNewTest::class
 */
#[When(env: 'test')]
class MaterialsEditTest extends KernelTestCase
{
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

        /** SeoCollectionDTO */

        $SeoCollection = $MaterialDTO->getSeo();

        /** @var MaterialSeoCollectionDTO $SeoCollectionDTO */
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

        foreach($MaterialDTO->getProperty() as $PropertyCollectionDTO)
        {
            self::assertEquals(CategoryMaterialSectionFieldUid::TEST, $PropertyCollectionDTO->getField());

            self::assertSame('Test New Property Value', $PropertyCollectionDTO->getValue());
            $PropertyCollectionDTO->setValue('Test Edit Property Value');
            self::assertSame('Test Edit Property Value', $PropertyCollectionDTO->getValue());
        }


        /** MaterialDescriptionDTO */

        $materialDescription = $MaterialDTO->getDescription();

        /** @var MaterialDescriptionDTO $materialDescriptionDto */
        foreach($materialDescription as $materialDescriptionDto)
        {
            self::assertSame('Test New Description', $materialDescriptionDto->getDescription());
            $materialDescriptionDto->setDescription('Test Edit Description');
            self::assertSame('Test Edit Description', $materialDescriptionDto->getDescription());

            self::assertSame('Test New Preview', $materialDescriptionDto->getPreview());
            $materialDescriptionDto->setPreview('Test Edit Preview');
            self::assertSame('Test Edit Preview', $materialDescriptionDto->getPreview());
        }

        /** InfoDTO */

        /** @var MaterialInfoDTO $InfoDTO */
        $InfoDTO = $MaterialDTO->getInfo();

        self::assertSame('Test New Info Article', $InfoDTO->getArticle());
        $InfoDTO->setArticle('Test Edit Info Article');
        self::assertSame('Test Edit Info Article', $InfoDTO->getArticle());

        self::assertSame(5, $InfoDTO->getSort());
        $InfoDTO->setSort(25);
        self::assertSame(25, $InfoDTO->getSort());

        self::assertSame('new_info_url', $InfoDTO->getUrl());
        $InfoDTO->setUrl('edit_info_url');
        self::assertSame('edit_info_url', $InfoDTO->getUrl());

        self::assertTrue($InfoDTO->getProfile()->equals(UserProfileUid::TEST));


        /** PriceDTO */

        /** @var MaterialPriceDTO $PriceDTO */
        $PriceDTO = $MaterialDTO->getPrice();

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
        $ActiveDTO = $MaterialDTO->getActive();


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

            self::assertSame('Test New Offer Postfix', $MaterialOffersCollectionDTO->getPostfix());
            $MaterialOffersCollectionDTO->setPostfix('Test Edit Offer Postfix');
            self::assertSame('Test Edit Offer Postfix', $MaterialOffersCollectionDTO->getPostfix());

            $MaterialOfferPriceDTO = $MaterialOffersCollectionDTO->getPrice();
            self::assertTrue($MaterialOfferPriceDTO->getPrice()->equals(55.5));

            $ModificationPriceMoney = new Money(50.5);
            $MaterialOfferPriceDTO->setPrice($ModificationPriceMoney);
            self::assertSame($ModificationPriceMoney, $MaterialOfferPriceDTO->getPrice());

            self::assertTrue($MaterialOfferPriceDTO->getCurrency()->equals(Currency::TEST));

            self::assertTrue(
                $MaterialOffersCollectionDTO
                    ->getCategoryOffer()
                    ->equals(CategoryMaterialOffersUid::TEST)
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
                        ->equals(Currency::TEST)
                );

                self::assertTrue(
                    $MaterialOffersVariationCollectionDTO
                        ->getConst()
                        ->equals(MaterialVariationConst::TEST)
                );

                self::assertSame(
                    'Test New Variation Article',
                    $MaterialOffersVariationCollectionDTO->getArticle()
                );

                $MaterialOffersVariationCollectionDTO->setArticle('Test Edit Variation Article');
                self::assertSame(
                    'Test Edit Variation Article',
                    $MaterialOffersVariationCollectionDTO->getArticle()
                );

                self::assertSame(
                    '200',
                    $MaterialOffersVariationCollectionDTO->getValue()
                );

                $MaterialOffersVariationCollectionDTO->setValue('Test Edit Variation Value');
                self::assertSame(
                    'Test Edit Variation Value',
                    $MaterialOffersVariationCollectionDTO->getValue()
                );

                self::assertSame(
                    'Test New Variation Postfix',
                    $MaterialOffersVariationCollectionDTO->getPostfix()
                );

                $MaterialOffersVariationCollectionDTO->setPostfix('Test Edit Variation Postfix');
                self::assertSame(
                    'Test Edit Variation Postfix',
                    $MaterialOffersVariationCollectionDTO->getPostfix()
                );

                self::assertTrue(
                    $MaterialOffersVariationCollectionDTO
                        ->getCategoryVariation()
                        ->equals(CategoryMaterialVariationUid::TEST)
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
                            ->equals(65.0)
                    );

                    $ModificationPriceMoney = new Money(50.5);
                    self::assertSame(50.5, $ModificationPriceMoney->getValue());

                    $MaterialModificationPriceDTO->setPrice($ModificationPriceMoney);
                    self::assertSame($ModificationPriceMoney, $MaterialModificationPriceDTO->getPrice());

                    $MaterialModificationPriceDTO->getCurrency()->equals(Currency::TEST);

                    $MaterialOffersVariationModificationCollectionDTO->setPrice($MaterialModificationPriceDTO);
                    self::assertSame(
                        $MaterialModificationPriceDTO,
                        $MaterialOffersVariationModificationCollectionDTO->getPrice()
                    );

                    self::assertTrue(
                        $MaterialOffersVariationModificationCollectionDTO
                            ->getConst()
                            ->equals(MaterialModificationConst::TEST)
                    );

                    self::assertSame(
                        'Test New Modification Article',
                        $MaterialOffersVariationModificationCollectionDTO->getArticle()
                    );

                    $MaterialOffersVariationModificationCollectionDTO->setArticle('Test Edit Modification Article');
                    self::assertSame(
                        'Test Edit Modification Article',
                        $MaterialOffersVariationModificationCollectionDTO->getArticle()
                    );

                    self::assertSame(
                        '300',
                        $MaterialOffersVariationModificationCollectionDTO->getValue()
                    );

                    $MaterialOffersVariationModificationCollectionDTO->setValue('Test Edit Modification Value');
                    self::assertSame(
                        'Test Edit Modification Value',
                        $MaterialOffersVariationModificationCollectionDTO->getValue()
                    );

                    self::assertSame(
                        'Test New Modification Postfix',
                        $MaterialOffersVariationModificationCollectionDTO->getPostfix()
                    );

                    $MaterialOffersVariationModificationCollectionDTO->setPostfix('Test Edit Modification Postfix');
                    self::assertSame(
                        'Test Edit Modification Postfix',
                        $MaterialOffersVariationModificationCollectionDTO->getPostfix()
                    );

                    self::assertTrue(
                        $MaterialOffersVariationModificationCollectionDTO
                            ->getCategoryModification()
                            ->equals(CategoryMaterialModificationUid::TEST)
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
