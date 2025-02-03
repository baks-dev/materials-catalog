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
use BaksDev\Materials\Catalog\Entity\Offers\Image\MaterialOfferImage;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Image\MaterialVariationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Image\MaterialModificationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Messenger\MaterialMessage;
use BaksDev\Materials\Catalog\Repository\UniqMaterialUrl\UniqMaterialUrlInterface;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Files\MaterialFilesCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Image\MaterialOfferImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Image\MaterialVariationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Image\MaterialModificationImageCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\MaterialPhotoCollectionDTO;
use Doctrine\ORM\EntityManagerInterface;

final class MaterialHandler extends AbstractHandler
{
    public function __construct(
        private readonly UniqMaterialUrlInterface $uniqMaterialUrl,

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

        $this->setCommand($command);

        $this->preEventPersistOrUpdate(Material::class, MaterialEvent::class);

        //        try
        //        {
        //            $command->getEvent() ? $this->preUpdate($command, true) : $this->prePersist($command);
        //        }
        //        catch(DomainException $errorUniqid)
        //        {
        //            return $errorUniqid->getMessage();
        //        }


        /** Проверяем уникальность семантической ссылки сырья */
        //$infoDTO = $command->getInfo();
        //$uniqMaterialUrl = $this->uniqMaterialUrl->isExists($infoDTO->getUrl(), $this->main->getId());

        //        if($uniqMaterialUrl)
        //        {
        //            $this->event->getInfo()->updateUrlUniq(); // Обновляем URL на уникальный с префиксом
        //        }

        // Загрузка базового фото галереи
        foreach($this->event->getPhoto() as $MaterialPhoto)
        {
            /** @var MaterialPhotoCollectionDTO $PhotoCollectionDTO */
            $PhotoCollectionDTO = $MaterialPhoto->getEntityDto();

            if(null !== $PhotoCollectionDTO->file)
            {
                $this->imageUpload->upload($PhotoCollectionDTO->file, $MaterialPhoto);
            }
        }


        // Загрузка файлов PDF галереи
        foreach($this->event->getFile() as $MaterialFile)
        {
            /** @var MaterialFilesCollectionDTO $FilesCollectionDTO */
            $FilesCollectionDTO = $MaterialFile->getEntityDto();

            if($FilesCollectionDTO->file !== null)
            {
                $this->fileUpload->upload($FilesCollectionDTO->file, $MaterialFile);
            }
        }


        /**
         * Загрузка фото торгового предложения.
         *
         * @var MaterialOffer $MaterialOffer
         */

        foreach($this->event->getOffer() as $MaterialOffer)
        {

            /** @var MaterialOfferImage $MaterialOfferImage */
            foreach($MaterialOffer->getImage() as $MaterialOfferImage)
            {
                /** @var MaterialOfferImageCollectionDTO $MaterialOfferImageCollectionDTO */
                $MaterialOfferImageCollectionDTO = $MaterialOfferImage->getEntityDto();

                if($MaterialOfferImageCollectionDTO->file !== null)
                {
                    $this->imageUpload->upload($MaterialOfferImageCollectionDTO->file, $MaterialOfferImage);
                }
            }

            /** @var MaterialVariation $MaterialVariation */
            foreach($MaterialOffer->getVariation() as $MaterialVariation)
            {
                /** @var MaterialVariationImage $MaterialVariationImage */
                foreach($MaterialVariation->getImage() as $MaterialVariationImage)
                {
                    /** @var MaterialVariationImageCollectionDTO $MaterialVariationImageCollectionDTO */
                    $MaterialVariationImageCollectionDTO = $MaterialVariationImage->getEntityDto();

                    if($MaterialVariationImageCollectionDTO->file !== null)
                    {
                        $this->imageUpload->upload($MaterialVariationImageCollectionDTO->file, $MaterialVariationImage);
                    }
                }

                /** @var MaterialModification $MaterialModification */
                foreach($MaterialVariation->getModification() as $MaterialModification)
                {

                    /** @var MaterialModificationImage $MaterialModificationImage */
                    foreach($MaterialModification->getImage() as $MaterialModificationImage)
                    {
                        /** @var MaterialModificationImageCollectionDTO $MaterialModificationImageCollectionDTO */
                        $MaterialModificationImageCollectionDTO = $MaterialModificationImage->getEntityDto();

                        if($MaterialModificationImageCollectionDTO->file !== null)
                        {
                            $this->imageUpload->upload($MaterialModificationImageCollectionDTO->file, $MaterialModificationImage);
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

        $this->flush();

        /* Отправляем событие в шину  */
        $this->messageDispatch->dispatch(
            message: new MaterialMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
            transport: 'materials-catalog',
        );

        return $this->main;

    }
}
