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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Materials\Catalog\Entity\Offers\ProductOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariation;
use BaksDev\Materials\Catalog\Messenger\MaterialMessage;
use BaksDev\Materials\Catalog\Repository\UniqProductUrl\UniqProductUrlInterface;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Files\FilesCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Image\ProductOfferImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Image\ProductVariationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Image\ProductModificationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\PhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Video\VideoCollectionDTO;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;

final class MaterialHandler extends AbstractHandler
{
    public function __construct(
        private readonly UniqProductUrlInterface $uniqProductUrl,

        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }

    public function handle(MaterialDTO $command): Material|string
    {

        /* Валидация DTO  */
        $this->validatorCollection->add($command);

        $this->main = new Material();
        $this->event = new MaterialEvent();

        try
        {
            $command->getEvent() ? $this->preUpdate($command, true) : $this->prePersist($command);
        }
        catch(DomainException $errorUniqid)
        {
            return $errorUniqid->getMessage();
        }


        /** Проверяем уникальность семантической ссылки продукта */
        $infoDTO = $command->getInfo();
        $uniqProductUrl = $this->uniqProductUrl->isExists($infoDTO->getUrl(), $this->main->getId());

        if($uniqProductUrl)
        {
            $this->event->getInfo()->updateUrlUniq(); // Обновляем URL на уникальный с префиксом
        }

        // Загрузка базового фото галереи
        foreach($this->event->getPhoto() as $ProductPhoto)
        {
            /** @var PhotoCollectionDTO $PhotoCollectionDTO */
            $PhotoCollectionDTO = $ProductPhoto->getEntityDto();

            if(null !== $PhotoCollectionDTO->file)
            {
                $this->imageUpload->upload($PhotoCollectionDTO->file, $ProductPhoto);
            }
        }


        // Загрузка файлов PDF галереи
        foreach($this->event->getFile() as $ProductFile)
        {
            /** @var FilesCollectionDTO $FilesCollectionDTO */
            $FilesCollectionDTO = $ProductFile->getEntityDto();

            if($FilesCollectionDTO->file !== null)
            {
                $this->fileUpload->upload($FilesCollectionDTO->file, $ProductFile);
            }
        }


        // Загрузка файлов Видео галереи
        foreach($this->event->getVideo() as $ProductVideo)
        {
            /** @var VideoCollectionDTO $VideoCollectionDTO */
            $VideoCollectionDTO = $ProductVideo->getEntityDto();

            if($VideoCollectionDTO->file !== null)
            {
                $this->fileUpload->upload($VideoCollectionDTO->file, $ProductVideo);
            }
        }


        /**
         * Загрузка фото торгового предложения.
         *
         * @var ProductOffer $ProductOffer
         */

        foreach($this->event->getOffer() as $ProductOffer)
        {

            /** @var ProductOfferImage $ProductOfferImage */
            foreach($ProductOffer->getImage() as $ProductOfferImage)
            {
                /** @var ProductOfferImageCollectionDTO $ProductOfferImageCollectionDTO */
                $ProductOfferImageCollectionDTO = $ProductOfferImage->getEntityDto();

                if($ProductOfferImageCollectionDTO->file !== null)
                {
                    $this->imageUpload->upload($ProductOfferImageCollectionDTO->file, $ProductOfferImage);
                }
            }

            /** @var ProductVariation $ProductVariation */
            foreach($ProductOffer->getVariation() as $ProductVariation)
            {
                /** @var ProductVariationImage $ProductVariationImage */
                foreach($ProductVariation->getImage() as $ProductVariationImage)
                {
                    /** @var ProductVariationImageCollectionDTO $ProductVariationImageCollectionDTO */
                    $ProductVariationImageCollectionDTO = $ProductVariationImage->getEntityDto();

                    if($ProductVariationImageCollectionDTO->file !== null)
                    {
                        $this->imageUpload->upload($ProductVariationImageCollectionDTO->file, $ProductVariationImage);
                    }
                }

                /** @var ProductModification $ProductModification */
                foreach($ProductVariation->getModification() as $ProductModification)
                {

                    /** @var ProductModificationImage $ProductModificationImage */
                    foreach($ProductModification->getImage() as $ProductModificationImage)
                    {
                        /** @var ProductModificationImageCollectionDTO $ProductModificationImageCollectionDTO */
                        $ProductModificationImageCollectionDTO = $ProductModificationImage->getEntityDto();

                        if($ProductModificationImageCollectionDTO->file !== null)
                        {
                            $this->imageUpload->upload($ProductModificationImageCollectionDTO->file, $ProductModificationImage);
                        }
                    }

                }
            }
        }


        /* Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->entityManager->flush();

        /* Отправляем событие в шину  */
        $this->messageDispatch->dispatch(
            message: new MaterialMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
            transport: 'materials-catalog',
        );

        return $this->main;

    }
}
