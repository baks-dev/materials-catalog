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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit;

use ArrayIterator;
use BaksDev\Core\Type\Device\Device;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEventInterface;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Active\ActiveDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\CategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Files\FilesCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Info\InfoDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\PhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price\PriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Property\PropertyCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Seo\SeoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Video\VideoCollectionDTO;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialEvent */
final class MaterialDTO implements MaterialEventInterface
{
    /** Идентификатор */
    #[Assert\Uuid]
    private ?MaterialEventUid $id = null;

    #[Assert\Valid]
    private InfoDTO $info;

    #[Assert\Valid]
    private ActiveDTO $active;

    #[Assert\Valid]
    private PriceDTO $price;

    #[Assert\Valid]
    private ArrayCollection $category;

    #[Assert\Valid]
    private ArrayCollection $file;

    #[Assert\Valid]
    private ArrayCollection $video;

    #[Assert\Valid]
    private ArrayCollection $offer;

    #[Assert\Valid]
    private ArrayCollection $photo;

    #[Assert\Valid]
    private ArrayCollection $property;

    #[Assert\Valid]
    private ArrayCollection $seo;

    #[Assert\Valid]
    private ArrayCollection $translate;

    #[Assert\Valid]
    private ArrayCollection $description;


    //private ArrayCollection $collectionProperty;

    public function __construct()
    {
        $this->info = new InfoDTO();
        $this->active = new ActiveDTO();
        $this->price = new PriceDTO();

        $this->category = new ArrayCollection();
        $this->file = new ArrayCollection();
        $this->offer = new ArrayCollection();
        $this->photo = new ArrayCollection();
        $this->property = new ArrayCollection();
        $this->seo = new ArrayCollection();
        $this->translate = new ArrayCollection();
        $this->video = new ArrayCollection();
        $this->description = new ArrayCollection();
    }


    public function getEvent(): ?MaterialEventUid
    {
        return $this->id;
    }


    public function setId(MaterialEventUid $id): void
    {
        $this->id = $id;
    }


    /* INFO  */

    public function getInfo(): InfoDTO
    {
        return $this->info;
    }


    public function setInfo(InfoDTO $info): void
    {
        $this->info = $info;
    }


    /* CATEGORIES */

    public function addCategory(CategoryCollectionDTO $category): void
    {
        $filter = $this->category->filter(function(CategoryCollectionDTO $element) use ($category) {
            return $category->getCategory()?->equals($element->getCategory());
        });

        if($filter->isEmpty())
        {
            $this->category[] = $category;
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getCategory(): ArrayCollection
    {
        if($this->category->isEmpty())
        {
            $CategoryCollectionDTO = new CategoryCollectionDTO();
            $this->addCategory($CategoryCollectionDTO);
        }

        return $this->category;
    }

    public function removeCategory(CategoryCollectionDTO $category): void
    {
        $this->category->removeElement($category);
    }


    /* ACTIVE */

    /**
     * @return ActiveDTO
     */
    public function getActive(): ActiveDTO
    {
        return $this->active;
    }


    /**
     * @param ActiveDTO $active
     */
    public function setActive(ActiveDTO $active): void
    {
        $this->active = $active;
    }


    /** Метод для инициализации и маппинга сущности на DTO в коллекции  */
    public function getActiveClass(): ActiveDTO
    {
        return new ActiveDTO();
    }

    /* FILES */

    /**
     * @return ArrayCollection
     */
    public function getFile(): ArrayCollection
    {
        if($this->file->isEmpty())
        {
            $this->addFile(new FilesCollectionDTO());
        }

        return $this->file;
    }


    public function addFile(FilesCollectionDTO $file): void
    {
        if(!$this->file->contains($file))
        {
            $this->file->add($file);
        }
    }


    public function removeFile(FilesCollectionDTO $file): void
    {
        $this->file->removeElement($file);
    }


    /* OFFERS */

    public function getOffer(): ArrayCollection
    {
        return $this->offer;
    }


    public function addOffer(Offers\ProductOffersCollectionDTO $offer): void
    {

        $filter = $this->offer->filter(function(Offers\ProductOffersCollectionDTO $element) use ($offer) {
            return $offer->getValue() === $element->getValue();
        });

        if($filter->isEmpty())
        {
            $this->offer->add($offer);
        }

    }


    public function removeOffer(Offers\ProductOffersCollectionDTO $offer): void
    {
        $this->offer->removeElement($offer);
    }


    /* PHOTOS */

    /**
     * @return ArrayCollection
     */
    public function getPhoto(): ArrayCollection
    {
        if($this->photo->isEmpty())
        {
            $this->addPhoto(new PhotoCollectionDTO());
        }

        return $this->photo;
    }


    public function addPhoto(PhotoCollectionDTO $photo): void
    {
        $filter = $this->photo->filter(function(PhotoCollectionDTO $element) use ($photo) {
            return !$photo->file && $photo->getName() === $element->getName();
        });

        if($filter->isEmpty())
        {
            $this->photo->add($photo);
        }
    }


    public function removePhoto(PhotoCollectionDTO $photo): void
    {
        $this->photo->removeElement($photo);
    }


    public function getVideo(): ArrayCollection
    {
        if($this->video->isEmpty())
        {
            $this->addVideo(new VideoCollectionDTO());
        }

        return $this->video;
    }


    public function addVideo(VideoCollectionDTO $video): void
    {
        if(!$this->video->contains($video))
        {
            $this->video->add($video);
        }
    }


    public function removeVideo(VideoCollectionDTO $video): void
    {
        $this->video->removeElement($video);
    }



    /* PRICE */

    /**
     * @return PriceDTO
     */
    public function getPrice(): PriceDTO
    {
        return $this->price;
    }


    /**
     * @param PriceDTO $price
     */
    public function setPrice(PriceDTO $price): void
    {
        $this->price = $price;
    }


    /* PROPERTIES */
    public function getProperty(): ArrayIterator
    {

        $iterator = $this->property->getIterator();

        $iterator->uasort(function($first, $second) {

            return $first->getSort() > $second->getSort() ? 1 : -1;
        });

        return $iterator;
    }


    public function addProperty(PropertyCollectionDTO $property): void
    {

        $filter = $this->property->filter(function(PropertyCollectionDTO $element) use ($property) {
            return $property->getField()?->equals($element->getField());
        });

        if($filter->isEmpty())
        {
            $this->property->add($property);
        }

    }


    public function removeProperty(PropertyCollectionDTO $property): void
    {
        $this->property->removeElement($property);
    }


    /* SEO  */

    public function removeSeo(SeoCollectionDTO $seo): void
    {
        $this->seo->removeElement($seo);
    }

    public function getSeo(): ArrayCollection
    {
        /* Вычисляем расхождение и добавляем неопределенные локали */
        foreach(Locale::diffLocale($this->seo) as $locale)
        {
            $CategorySeoDTO = new SeoCollectionDTO();
            $CategorySeoDTO->setLocal($locale);
            $this->addSeo($CategorySeoDTO);
        }

        return $this->seo;
    }

    public function addSeo(SeoCollectionDTO $seo): void
    {
        if(empty($seo->getLocal()->getLocalValue()))
        {
            return;
        }

        if(!$this->seo->contains($seo))
        {
            $this->seo->add($seo);
        }
    }


    /* TRANS */

    public function getTranslate(): ArrayCollection
    {
        /* Вычисляем расхождение и добавляем неопределенные локали */
        foreach(Locale::diffLocale($this->translate) as $locale)
        {
            $ProductTransDTO = new Trans\MaterialTransDTO();
            $ProductTransDTO->setLocal($locale);
            $this->addTranslate($ProductTransDTO);
        }

        return $this->translate;
    }

    public function setTranslate(ArrayCollection $trans): void
    {
        $this->translate = $trans;
    }

    public function addTranslate(Trans\MaterialTransDTO $trans): void
    {
        if(empty($trans->getLocal()->getLocalValue()))
        {
            return;
        }

        if(!$this->translate->contains($trans))
        {
            $this->translate->add($trans);
        }
    }


    /* DESCRIPTION */

    public function getDescription(): ArrayCollection
    {

        /* Вычисляем расхождение и добавляем неопределенные локали */
        foreach(Locale::diffLocale($this->description) as $locale)
        {
            /** @var Device $device */
            foreach(Device::cases() as $device)
            {
                $ProductDescriptionDTO = new Description\ProductDescriptionDTO();
                $ProductDescriptionDTO->setLocal($locale);
                $ProductDescriptionDTO->setDevice($device);
                $this->addDescription($ProductDescriptionDTO);
            }
        }

        return $this->description;
    }

    public function setDescription(ArrayCollection $description): void
    {
        $this->description = $description;
    }

    public function addDescription(Description\ProductDescriptionDTO $description): void
    {
        if(empty($description->getLocal()->getLocalValue()))
        {
            return;
        }

        if(!$this->description->contains($description))
        {
            $this->description->add($description);
        }
    }

}
