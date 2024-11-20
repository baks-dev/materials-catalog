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

use BaksDev\Core\BaksDevCoreBundle;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
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
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Image\ProductOfferImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Price\ProductOfferPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\ProductOffersCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Image\ProductVariationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Image\ProductModificationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Price\ProductModificationPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\ProductModificationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Price\ProductVariationPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\ProductVariationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\PhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price\PriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Property\PropertyCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Seo\SeoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Trans\MaterialTransDTO;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Category\Type\Offers\Id\CategoryProductOffersUid;
use BaksDev\Products\Category\Type\Offers\Modification\CategoryProductModificationUid;
use BaksDev\Products\Category\Type\Offers\Variation\CategoryProductVariationUid;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use BaksDev\Products\Category\UseCase\Admin\NewEdit\CategoryProductDTO;
use BaksDev\Products\Category\UseCase\Admin\NewEdit\Tests\CategoryProductNewTest;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @group materials-catalog
 * @group materials-catalog-usecase
 */
#[When(env: 'test')]
class ProductsProductNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(Material::class)
            ->findOneBy(['id' => MaterialUid::TEST]);

        if($main)
        {
            $em->remove($main);
        }

        $event = $em->getRepository(MaterialEvent::class)
            ->findBy(['main' => MaterialUid::TEST]);

        foreach($event as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
        $em->clear();


        /** Создаем тестовую категорию */
        CategoryProductNewTest::setUpBeforeClass();
        (new CategoryProductNewTest())->testUseCase();

    }


    public function testUseCase(): void
    {

        /** @var ContainerBagInterface $containerBag */
        $container = self::getContainer();
        $containerBag = $container->get(ContainerBagInterface::class);
        $fileSystem = $container->get(Filesystem::class);

        /** Создаем путь к тестовой директории */
        $testUploadDir = implode(DIRECTORY_SEPARATOR, [$containerBag->get('kernel.project_dir'), 'public', 'upload', 'tests']);

        /** Проверяем существование директории для тестовых картинок */
        if(false === is_dir($testUploadDir))
        {
            $fileSystem->mkdir($testUploadDir);
        }


        /** Создаем объект ProductDTO */


        /** @see CategoryProductDTO */
        $ProductDTO = new MaterialDTO();

        /** CategoryCollectionDTO */

        $CategoryCollectionDTO = new CategoryCollectionDTO();

        $CategoryCollectionDTO->setCategory($categoryProductUid = new CategoryProductUid());
        self::assertSame($CategoryCollectionDTO->getCategory(), $categoryProductUid);

        $CategoryCollectionDTO->setRoot(true);
        self::assertTrue($CategoryCollectionDTO->getRoot());

        $ProductDTO->addCategory($CategoryCollectionDTO);


        /** SeoCollectionDTO */

        $SeoCollection = $ProductDTO->getSeo();

        /** @var SeoCollectionDTO $SeoCollectionDTO */
        foreach($SeoCollection as $SeoCollectionDTO)
        {
            $SeoCollectionDTO->setTitle('Test New Title');
            self::assertSame('Test New Title', $SeoCollectionDTO->getTitle());

            $SeoCollectionDTO->setKeywords('Test New Keywords');
            self::assertSame('Test New Keywords', $SeoCollectionDTO->getKeywords());

            $SeoCollectionDTO->setDescription('Test New Description');
            self::assertSame('Test New Description', $SeoCollectionDTO->getDescription());
        }

        $ProductDTO->addSeo($SeoCollectionDTO);


        /** PhotoCollectionDTO */


        /**
         * Создаем тестовый файл загрузки Photo Collection
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp']
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'photo.webp'
        );

        $filePhoto = new File($testUploadDir.DIRECTORY_SEPARATOR.'photo.webp', false);


        $PhotoCollectionDTO = new PhotoCollectionDTO();
        $PhotoCollectionDTO->file = $filePhoto;

        $PhotoCollectionDTO->setRoot(true);
        self::assertTrue($PhotoCollectionDTO->getRoot());

        $ProductDTO->addPhoto($PhotoCollectionDTO);


        /** PropertyCollectionDTO */

        $PropertyCollectionDTO = new PropertyCollectionDTO();

        $PropertyCollectionDTO->setSort(5);
        self::assertSame(5, $PropertyCollectionDTO->getSort());

        $PropertyCollectionDTO->setValue('Test New Property Value');
        self::assertSame('Test New Property Value', $PropertyCollectionDTO->getValue());

        $PropertyCollectionDTO->setField($categoryProductSectionFieldUid = new CategoryProductSectionFieldUid());
        self::assertSame($categoryProductSectionFieldUid, $PropertyCollectionDTO->getField());

        $PropertyCollectionDTO->setSection('Test New Property Section');
        self::assertSame('Test New Property Section', $PropertyCollectionDTO->getSection());

        $ProductDTO->addProperty($PropertyCollectionDTO);

        /** ProductDescriptionDTO */

        $productDescription = $ProductDTO->getDescription();

        /** @var ProductDescriptionDTO $productDescriptionDto */
        foreach($productDescription as $productDescriptionDto)
        {
            $productDescriptionDto->setDescription('Test New Description');
            self::assertSame('Test New Description', $productDescriptionDto->getDescription());

            $productDescriptionDto->setPreview('Test New Preview');
            self::assertSame('Test New Preview', $productDescriptionDto->getPreview());
        }


        /** InfoDTO */

        $InfoDTO = new InfoDTO();

        $InfoDTO->setArticle('Test New Info Article');
        self::assertSame('Test New Info Article', $InfoDTO->getArticle());

        $InfoDTO->setSort(5);
        self::assertSame(5, $InfoDTO->getSort());

        $InfoDTO->setUrl('test_new_info_url');
        self::assertSame('test_new_info_url', $InfoDTO->getUrl());

        $InfoDTO->setProfile($UserProfileUid = new UserProfileUid());
        self::assertSame($UserProfileUid, $InfoDTO->getProfile());

        $ProductDTO->setInfo($InfoDTO);


        /** PriceDTO */

        $PriceDTO = new PriceDTO();

        $PriceMoney = new Money(50.0);
        self::assertSame(50.0, $PriceMoney->getValue());

        $PriceDTO->setPrice($PriceMoney);
        self::assertSame($PriceMoney, $PriceDTO->getPrice());

        $PriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $PriceDTO->getCurrency());

        $PriceDTO->setRequest(true);
        self::assertTrue($PriceDTO->getRequest());

        $ProductDTO->setPrice($PriceDTO);


        /** ActiveDTO */

        $ActiveDTO = new ActiveDTO();

        $ActiveDTO->setActive(true);
        self::assertTrue($ActiveDTO->getActive());

        $activeTestDateNew = new DateTimeImmutable('2024-09-15 15:12:00');

        $ActiveDTO->setActiveFrom($activeTestDateNew);
        self::assertSame($activeTestDateNew, $ActiveDTO->getActiveFrom());

        $ActiveDTO->setActiveFromTime($activeTestDateNew);
        self::assertSame($activeTestDateNew, $ActiveDTO->getActiveFromTime());

        $ActiveDTO->setActive(false);
        self::assertFalse($ActiveDTO->getActive());

        $ActiveDTO->setActiveTo($activeTestDateNew);
        self::assertSame($activeTestDateNew, $ActiveDTO->getActiveTo());

        $ActiveDTO->setActiveToTime($activeTestDateNew);
        self::assertSame($activeTestDateNew, $ActiveDTO->getActiveToTime());

        $ProductDTO->setActive($ActiveDTO);


        /** ProductTransDTO */

        $ProductTrans = $ProductDTO->getTranslate();

        /** @var MaterialTransDTO $ProductTransDTO */
        foreach($ProductTrans as $ProductTransDTO)
        {
            $ProductTransDTO->setName('RU_ru');
            self::assertSame('RU_ru', $ProductTransDTO->getName());
        }


        ///////////////////////////////////////////
        ////////////////// Offer //////////////////
        ///////////////////////////////////////////

        /** ProductOffersCollectionDTO */

        $ProductOffersCollectionDTO = new ProductOffersCollectionDTO();

        $ProductOffersCollectionDTO->setValue('Test New Offer Value');
        self::assertSame('Test New Offer Value', $ProductOffersCollectionDTO->getValue());

        $ProductOffersCollectionDTO->setPostfix('Test New Offer Postfix');
        self::assertSame('Test New Offer Postfix', $ProductOffersCollectionDTO->getPostfix());

        $ModificationPriceMoney = new Money(55.5);
        self::assertSame(55.5, $ModificationPriceMoney->getValue());

        $ProductOfferPriceDTO = new ProductOfferPriceDTO();

        $ProductOfferPriceDTO->setPrice($ModificationPriceMoney);

        $ProductOfferPriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $ProductOfferPriceDTO->getCurrency());

        $ProductOffersCollectionDTO->setPrice($ProductOfferPriceDTO);
        self::assertSame($ProductOfferPriceDTO, $ProductOffersCollectionDTO->getPrice());

        $ProductOffersCollectionDTO->setCategoryOffer($CategoryProductOffersUid = new CategoryProductOffersUid());
        self::assertSame($CategoryProductOffersUid, $ProductOffersCollectionDTO->getCategoryOffer());

        $ProductOffersCollectionDTO->setArticle('Test New Offer Article');
        self::assertSame('Test New Offer Article', $ProductOffersCollectionDTO->getArticle());

        $ProductOffersCollectionDTO->setConst($ProductOfferConst = new ProductOfferConst());
        self::assertSame($ProductOfferConst, $ProductOffersCollectionDTO->getConst());


        /**
         * Создаем тестовый файл загрузки ProductVariationImage
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp']
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'offer.webp'
        );

        $ProductOfferImage = new File($testUploadDir.DIRECTORY_SEPARATOR.'offer.webp', false);

        $ProductOfferImageCollectionDTO = new ProductOfferImageCollectionDTO();

        $ProductOfferImageCollectionDTO->file = $ProductOfferImage;
        self::assertSame($ProductOfferImage, $ProductOfferImageCollectionDTO->file);

        $ProductOffersCollectionDTO->addImage($ProductOfferImageCollectionDTO);

        $ProductDTO->addOffer($ProductOffersCollectionDTO);


        ///////////////////////////////////////////
        ///////////////// Variation ///////////////
        ///////////////////////////////////////////

        /** ProductOffersVariationCollectionDTO */

        $ProductOffersVariationCollectionDTO = new ProductVariationCollectionDTO();

        $ProductVariationPriceDTO = new ProductVariationPriceDTO();

        $ModificationPriceMoney = new Money(55.0);
        self::assertSame(55.0, $ModificationPriceMoney->getValue());

        $ProductVariationPriceDTO->setPrice($ModificationPriceMoney);
        self::assertSame($ModificationPriceMoney, $ProductVariationPriceDTO->getPrice());

        $ProductVariationPriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $ProductVariationPriceDTO->getCurrency());

        $ProductOffersVariationCollectionDTO->setPrice($ProductVariationPriceDTO);
        self::assertSame($ProductVariationPriceDTO, $ProductOffersVariationCollectionDTO->getPrice());

        $ProductOffersVariationCollectionDTO->setConst($ProductVariationConst = new ProductVariationConst());
        self::assertSame($ProductVariationConst, $ProductOffersVariationCollectionDTO->getConst());

        $ProductOffersVariationCollectionDTO->setArticle('Test New Variation Article');
        self::assertSame('Test New Variation Article', $ProductOffersVariationCollectionDTO->getArticle());

        $ProductOffersVariationCollectionDTO->setValue('Test New Variation Value');
        self::assertSame('Test New Variation Value', $ProductOffersVariationCollectionDTO->getValue());

        $ProductOffersVariationCollectionDTO->setPostfix('Test New Variation Postfix');
        self::assertSame('Test New Variation Postfix', $ProductOffersVariationCollectionDTO->getPostfix());

        $ProductOffersVariationCollectionDTO->setCategoryVariation(
            new CategoryProductVariationUid(CategoryProductVariationUid::TEST)
        );


        $ProductVariationImageCollectionDTO = new ProductVariationImageCollectionDTO();


        /**
         * Создаем тестовый файл загрузки ProductVariationImage
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp']
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'variation.webp'
        );

        $ProductVariationImage = new File($testUploadDir.DIRECTORY_SEPARATOR.'variation.webp', false);


        $ProductVariationImageCollectionDTO->file = $ProductVariationImage;
        self::assertSame($ProductVariationImage, $ProductVariationImageCollectionDTO->file);

        $ProductOffersVariationCollectionDTO->addImage($ProductVariationImageCollectionDTO);
        self::assertSame($ProductVariationImageCollectionDTO, $ProductOffersVariationCollectionDTO->getImage()->current());

        $ProductOffersCollectionDTO->addVariation($ProductOffersVariationCollectionDTO);


        ///////////////////////////////////////////
        ////////////// Modification ///////////////
        ///////////////////////////////////////////

        /** ProductOffersVariationModificationCollectionDTO */

        $ProductOffersVariationModificationCollectionDTO = new ProductModificationCollectionDTO();

        $ProductModificationPriceDTO = new ProductModificationPriceDTO();

        $ModificationPriceMoney = new Money(65.0);
        self::assertSame(65.0, $ModificationPriceMoney->getValue());

        $ProductModificationPriceDTO->setPrice($ModificationPriceMoney);
        self::assertSame($ModificationPriceMoney, $ProductModificationPriceDTO->getPrice());

        $ProductModificationPriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $ProductModificationPriceDTO->getCurrency());

        $ProductOffersVariationModificationCollectionDTO->setPrice($ProductModificationPriceDTO);
        self::assertSame(
            $ProductModificationPriceDTO,
            $ProductOffersVariationModificationCollectionDTO->getPrice()
        );

        $ProductOffersVariationModificationCollectionDTO->setConst(
            $ProductModificationConst = new ProductModificationConst()
        );
        self::assertSame(
            $ProductModificationConst,
            $ProductOffersVariationModificationCollectionDTO->getConst()
        );

        $ProductOffersVariationModificationCollectionDTO->setArticle('Test New Modification Article');
        self::assertSame(
            'Test New Modification Article',
            $ProductOffersVariationModificationCollectionDTO->getArticle()
        );


        $ProductOffersVariationModificationCollectionDTO->setValue('Test New Modification Value');
        self::assertSame(
            'Test New Modification Value',
            $ProductOffersVariationModificationCollectionDTO->getValue()
        );

        $ProductOffersVariationModificationCollectionDTO->setPostfix('Test New Modification Postfix');
        self::assertSame(
            'Test New Modification Postfix',
            $ProductOffersVariationModificationCollectionDTO->getPostfix()
        );

        $ProductOffersVariationModificationCollectionDTO->setCategoryModification(
            new CategoryProductModificationUid(CategoryProductModificationUid::TEST)
        );

        $ProductModificationImageCollectionDTO = new ProductModificationImageCollectionDTO();


        /**
         * Создаем тестовый файл загрузки ProductModificationImage
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp']
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'modification.webp'
        );

        $ProductModificationImage = new File($testUploadDir.DIRECTORY_SEPARATOR.'modification.webp', false);

        $ProductModificationImageCollectionDTO->file = $ProductModificationImage;
        self::assertSame($ProductModificationImage, $ProductModificationImageCollectionDTO->file);

        $ProductOffersVariationModificationCollectionDTO->addImage($ProductModificationImageCollectionDTO);
        self::assertSame(
            $ProductModificationImageCollectionDTO,
            $ProductOffersVariationModificationCollectionDTO->getImage()->current()
        );

        $ProductOffersVariationCollectionDTO->addModification($ProductOffersVariationModificationCollectionDTO);


        /** @var MaterialHandler $ProductHandler */
        $ProductHandler = self::getContainer()->get(MaterialHandler::class);
        $handle = $ProductHandler->handle($ProductDTO);

        self::assertTrue(($handle instanceof Material));

    }
}
