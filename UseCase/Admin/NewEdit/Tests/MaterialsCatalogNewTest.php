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

use BaksDev\Core\BaksDevCoreBundle;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\MaterialCategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialHandler;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Image\MaterialOfferImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\MaterialOffersCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Price\MaterialOfferPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Image\MaterialVariationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\MaterialVariationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Image\MaterialModificationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\MaterialModificationCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Price\MaterialModificationPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Price\MaterialVariationPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\MaterialPhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price\MaterialPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Trans\MaterialTransDTO;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Materials\Category\Type\Offers\Id\CategoryMaterialOffersUid;
use BaksDev\Materials\Category\Type\Offers\Modification\CategoryMaterialModificationUid;
use BaksDev\Materials\Category\Type\Offers\Variation\CategoryMaterialVariationUid;
use BaksDev\Materials\Category\UseCase\Admin\NewEdit\CategoryMaterialDTO;
use BaksDev\Materials\Category\UseCase\Admin\NewEdit\Tests\CategoryMaterialNewTest;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
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

#[When(env: 'test')]
#[Group('materials-catalog')]
class MaterialsCatalogNewTest extends KernelTestCase
{
    public const string OFFER_VALUE = '100';
    public const string VARIATION_VALUE = '200';
    public const string MODIFICATION_VALUE = '300';

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
        CategoryMaterialNewTest::setUpBeforeClass();
        (new CategoryMaterialNewTest(''))->testUseCase();

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


        /** Создаем объект MaterialDTO */


        /** @see CategoryMaterialDTO */
        $MaterialDTO = new MaterialDTO();

        /** CategoryCollectionDTO */

        $CategoryCollectionDTO = new MaterialCategoryCollectionDTO();

        $CategoryCollectionDTO->setCategory($categoryMaterialUid = new CategoryMaterialUid());
        self::assertSame($CategoryCollectionDTO->getCategory(), $categoryMaterialUid);

        $CategoryCollectionDTO->setRoot(true);
        self::assertTrue($CategoryCollectionDTO->getRoot());

        $MaterialDTO->addCategory($CategoryCollectionDTO);


        /** PhotoCollectionDTO */


        /**
         * Создаем тестовый файл загрузки Photo Collection
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp'],
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'photo.webp',
        );

        $filePhoto = new File($testUploadDir.DIRECTORY_SEPARATOR.'photo.webp', false);


        $PhotoCollectionDTO = new MaterialPhotoCollectionDTO();
        $PhotoCollectionDTO->file = $filePhoto;

        $PhotoCollectionDTO->setRoot(true);
        self::assertTrue($PhotoCollectionDTO->getRoot());

        $MaterialDTO->addPhoto($PhotoCollectionDTO);


        /** PriceDTO */

        $PriceDTO = new MaterialPriceDTO();

        $PriceMoney = new Money(50.0);
        self::assertSame(50.0, $PriceMoney->getValue());

        $PriceDTO->setPrice($PriceMoney);
        self::assertSame($PriceMoney, $PriceDTO->getPrice());

        $PriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $PriceDTO->getCurrency());

        //        $PriceDTO->setRequest(true);
        //        self::assertTrue($PriceDTO->getRequest());

        $MaterialDTO->setPrice($PriceDTO);


        /** MaterialTransDTO */

        $MaterialTrans = $MaterialDTO->getTranslate();

        /** @var MaterialTransDTO $MaterialTransDTO */
        foreach($MaterialTrans as $MaterialTransDTO)
        {
            $MaterialTransDTO->setName('RU_ru');
            self::assertSame('RU_ru', $MaterialTransDTO->getName());
        }


        ///////////////////////////////////////////
        ////////////////// Offer //////////////////
        ///////////////////////////////////////////

        /** MaterialOffersCollectionDTO */

        $MaterialOffersCollectionDTO = new MaterialOffersCollectionDTO();

        $MaterialOffersCollectionDTO->setValue(self::OFFER_VALUE);
        self::assertSame('100', $MaterialOffersCollectionDTO->getValue());

        //$MaterialOffersCollectionDTO->setPostfix('Test New Offer Postfix');
        //self::assertSame('Test New Offer Postfix', $MaterialOffersCollectionDTO->getPostfix());

        $ModificationPriceMoney = new Money(55.5);
        self::assertSame(55.5, $ModificationPriceMoney->getValue());

        $MaterialOfferPriceDTO = new MaterialOfferPriceDTO();

        $MaterialOfferPriceDTO->setPrice($ModificationPriceMoney);

        $MaterialOfferPriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $MaterialOfferPriceDTO->getCurrency());

        $MaterialOffersCollectionDTO->setPrice($MaterialOfferPriceDTO);
        self::assertSame($MaterialOfferPriceDTO, $MaterialOffersCollectionDTO->getPrice());

        $MaterialOffersCollectionDTO->setCategoryOffer($CategoryMaterialOffersUid = new CategoryMaterialOffersUid());
        self::assertSame($CategoryMaterialOffersUid, $MaterialOffersCollectionDTO->getCategoryOffer());

        $MaterialOffersCollectionDTO->setArticle('Test New Offer Article');
        self::assertSame('Test New Offer Article', $MaterialOffersCollectionDTO->getArticle());

        $MaterialOffersCollectionDTO->setConst($MaterialOfferConst = new MaterialOfferConst());
        self::assertSame($MaterialOfferConst, $MaterialOffersCollectionDTO->getConst());


        /**
         * Создаем тестовый файл загрузки MaterialVariationImage
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp'],
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'offer.webp',
        );

        $MaterialOfferImage = new File($testUploadDir.DIRECTORY_SEPARATOR.'offer.webp', false);

        $MaterialOfferImageCollectionDTO = new MaterialOfferImageCollectionDTO();

        $MaterialOfferImageCollectionDTO->file = $MaterialOfferImage;
        self::assertSame($MaterialOfferImage, $MaterialOfferImageCollectionDTO->file);

        $MaterialOffersCollectionDTO->addImage($MaterialOfferImageCollectionDTO);

        $MaterialDTO->addOffer($MaterialOffersCollectionDTO);


        ///////////////////////////////////////////
        ///////////////// Variation ///////////////
        ///////////////////////////////////////////

        /** MaterialOffersVariationCollectionDTO */

        $MaterialOffersVariationCollectionDTO = new MaterialVariationCollectionDTO();

        $MaterialVariationPriceDTO = new MaterialVariationPriceDTO();

        $ModificationPriceMoney = new Money(55.0);
        self::assertSame(55.0, $ModificationPriceMoney->getValue());

        $MaterialVariationPriceDTO->setPrice($ModificationPriceMoney);
        self::assertSame($ModificationPriceMoney, $MaterialVariationPriceDTO->getPrice());

        $MaterialVariationPriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $MaterialVariationPriceDTO->getCurrency());

        $MaterialOffersVariationCollectionDTO->setPrice($MaterialVariationPriceDTO);
        self::assertSame($MaterialVariationPriceDTO, $MaterialOffersVariationCollectionDTO->getPrice());

        $MaterialOffersVariationCollectionDTO->setConst($MaterialVariationConst = new MaterialVariationConst());
        self::assertSame($MaterialVariationConst, $MaterialOffersVariationCollectionDTO->getConst());

        $MaterialOffersVariationCollectionDTO->setArticle('Test New Variation Article');
        self::assertSame('Test New Variation Article', $MaterialOffersVariationCollectionDTO->getArticle());

        $MaterialOffersVariationCollectionDTO->setValue(self::VARIATION_VALUE);
        self::assertSame('200', $MaterialOffersVariationCollectionDTO->getValue());

        //$MaterialOffersVariationCollectionDTO->setPostfix('Test New Variation Postfix');
        //self::assertSame('Test New Variation Postfix', $MaterialOffersVariationCollectionDTO->getPostfix());

        $MaterialOffersVariationCollectionDTO->setCategoryVariation(
            new CategoryMaterialVariationUid(CategoryMaterialVariationUid::TEST),
        );


        $MaterialVariationImageCollectionDTO = new MaterialVariationImageCollectionDTO();


        /**
         * Создаем тестовый файл загрузки MaterialVariationImage
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp'],
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'variation.webp',
        );

        $MaterialVariationImage = new File($testUploadDir.DIRECTORY_SEPARATOR.'variation.webp', false);


        $MaterialVariationImageCollectionDTO->file = $MaterialVariationImage;
        self::assertSame($MaterialVariationImage, $MaterialVariationImageCollectionDTO->file);

        $MaterialOffersVariationCollectionDTO->addImage($MaterialVariationImageCollectionDTO);
        self::assertSame($MaterialVariationImageCollectionDTO, $MaterialOffersVariationCollectionDTO->getImage()->current());

        $MaterialOffersCollectionDTO->addVariation($MaterialOffersVariationCollectionDTO);


        ///////////////////////////////////////////
        ////////////// Modification ///////////////
        ///////////////////////////////////////////

        /** MaterialOffersVariationModificationCollectionDTO */

        $MaterialOffersVariationModificationCollectionDTO = new MaterialModificationCollectionDTO();

        $MaterialModificationPriceDTO = new MaterialModificationPriceDTO();

        $ModificationPriceMoney = new Money(65.0);
        self::assertSame(65.0, $ModificationPriceMoney->getValue());

        $MaterialModificationPriceDTO->setPrice($ModificationPriceMoney);
        self::assertSame($ModificationPriceMoney, $MaterialModificationPriceDTO->getPrice());

        $MaterialModificationPriceDTO->setCurrency($Currency = new Currency());
        self::assertSame($Currency, $MaterialModificationPriceDTO->getCurrency());

        $MaterialOffersVariationModificationCollectionDTO->setPrice($MaterialModificationPriceDTO);
        self::assertSame(
            $MaterialModificationPriceDTO,
            $MaterialOffersVariationModificationCollectionDTO->getPrice(),
        );

        $MaterialOffersVariationModificationCollectionDTO->setConst(
            $MaterialModificationConst = new MaterialModificationConst(),
        );
        self::assertSame(
            $MaterialModificationConst,
            $MaterialOffersVariationModificationCollectionDTO->getConst(),
        );

        $MaterialOffersVariationModificationCollectionDTO->setArticle('Test New Modification Article');
        self::assertSame(
            'Test New Modification Article',
            $MaterialOffersVariationModificationCollectionDTO->getArticle(),
        );


        $MaterialOffersVariationModificationCollectionDTO->setValue(self::MODIFICATION_VALUE);
        self::assertSame(
            '300',
            $MaterialOffersVariationModificationCollectionDTO->getValue(),
        );

        //        $MaterialOffersVariationModificationCollectionDTO->setPostfix('Test New Modification Postfix');
        //        self::assertSame(
        //            'Test New Modification Postfix',
        //            $MaterialOffersVariationModificationCollectionDTO->getPostfix()
        //        );

        $MaterialOffersVariationModificationCollectionDTO->setCategoryModification(
            new CategoryMaterialModificationUid(CategoryMaterialModificationUid::TEST),
        );

        $MaterialModificationImageCollectionDTO = new MaterialModificationImageCollectionDTO();


        /**
         * Создаем тестовый файл загрузки MaterialModificationImage
         */
        $fileSystem->copy(
            BaksDevCoreBundle::PATH.implode(
                DIRECTORY_SEPARATOR,
                ['Resources', 'assets', 'img', 'empty.webp'],
            ),
            $testUploadDir.DIRECTORY_SEPARATOR.'modification.webp',
        );

        $MaterialModificationImage = new File($testUploadDir.DIRECTORY_SEPARATOR.'modification.webp', false);

        $MaterialModificationImageCollectionDTO->file = $MaterialModificationImage;
        self::assertSame($MaterialModificationImage, $MaterialModificationImageCollectionDTO->file);

        $MaterialOffersVariationModificationCollectionDTO->addImage($MaterialModificationImageCollectionDTO);
        self::assertSame(
            $MaterialModificationImageCollectionDTO,
            $MaterialOffersVariationModificationCollectionDTO->getImage()->current(),
        );

        $MaterialOffersVariationCollectionDTO->addModification($MaterialOffersVariationModificationCollectionDTO);

        /** @var MaterialHandler $MaterialHandler */
        $MaterialHandler = self::getContainer()->get(MaterialHandler::class);
        $handle = $MaterialHandler->handle($MaterialDTO);

        self::assertTrue(($handle instanceof Material));

    }
}
