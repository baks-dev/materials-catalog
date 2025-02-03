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

use ArrayIterator;
use BaksDev\Core\Type\Device\Device;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEventInterface;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Active\ActiveDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\MaterialCategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Files\MaterialFilesCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Info\MaterialInfoDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Photo\MaterialPhotoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price\MaterialPriceDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Property\PropertyCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Seo\MaterialSeoCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Video\MaterialVideoCollectionDTO;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialEvent */
final class MaterialDTO implements MaterialEventInterface
{
    /** Идентификатор */
    #[Assert\Uuid]
    private ?MaterialEventUid $id = null;

    #[Assert\Valid]
    private MaterialInfoDTO $info;

    #[Assert\Valid]
    private MaterialPriceDTO $price;

    #[Assert\Valid]
    private ArrayCollection $category;

    #[Assert\Valid]
    private ArrayCollection $file;

    #[Assert\Valid]
    private ArrayCollection $offer;

    #[Assert\Valid]
    private ArrayCollection $photo;

    #[Assert\Valid]
    private ArrayCollection $translate;


    //private ArrayCollection $collectionProperty;

    public function __construct()
    {
        $this->info = new MaterialInfoDTO();

        $this->price = new MaterialPriceDTO();

        $this->category = new ArrayCollection();
        $this->file = new ArrayCollection();
        $this->offer = new ArrayCollection();
        $this->photo = new ArrayCollection();

        $this->translate = new ArrayCollection();

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

    public function getInfo(): MaterialInfoDTO
    {
        return $this->info;
    }


    public function setInfo(MaterialInfoDTO $info): void
    {
        $this->info = $info;
    }


    /* CATEGORIES */

    public function addCategory(MaterialCategoryCollectionDTO $category): void
    {
        $filter = $this->category->filter(function(MaterialCategoryCollectionDTO $element) use ($category) {
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
            $CategoryCollectionDTO = new MaterialCategoryCollectionDTO();
            $this->addCategory($CategoryCollectionDTO);
        }

        return $this->category;
    }

    public function removeCategory(MaterialCategoryCollectionDTO $category): void
    {
        $this->category->removeElement($category);
    }


    /* FILES */

    /**
     * @return ArrayCollection
     */
    public function getFile(): ArrayCollection
    {
        if($this->file->isEmpty())
        {
            $this->addFile(new MaterialFilesCollectionDTO());
        }

        return $this->file;
    }


    public function addFile(MaterialFilesCollectionDTO $file): void
    {
        if(!$this->file->contains($file))
        {
            $this->file->add($file);
        }
    }


    public function removeFile(MaterialFilesCollectionDTO $file): void
    {
        $this->file->removeElement($file);
    }


    /* OFFERS */

    public function getOffer(): ArrayCollection
    {
        return $this->offer;
    }


    public function addOffer(Offers\MaterialOffersCollectionDTO $offer): void
    {

        $filter = $this->offer->filter(function(Offers\MaterialOffersCollectionDTO $element) use ($offer) {
            return $offer->getValue() === $element->getValue();
        });

        if($filter->isEmpty())
        {
            $this->offer->add($offer);
        }

    }


    public function removeOffer(Offers\MaterialOffersCollectionDTO $offer): void
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
            $this->addPhoto(new MaterialPhotoCollectionDTO());
        }

        return $this->photo;
    }


    public function addPhoto(MaterialPhotoCollectionDTO $photo): void
    {
        $filter = $this->photo->filter(function(MaterialPhotoCollectionDTO $element) use ($photo) {
            return !$photo->file && $photo->getName() === $element->getName();
        });

        if($filter->isEmpty())
        {
            $this->photo->add($photo);
        }
    }


    public function removePhoto(MaterialPhotoCollectionDTO $photo): void
    {
        $this->photo->removeElement($photo);
    }



    /* PRICE */

    /**
     * @return MaterialPriceDTO
     */
    public function getPrice(): MaterialPriceDTO
    {
        return $this->price;
    }


    /**
     * @param MaterialPriceDTO $price
     */
    public function setPrice(MaterialPriceDTO $price): void
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




    /* TRANS */

    public function getTranslate(): ArrayCollection
    {
        /* Вычисляем расхождение и добавляем неопределенные локали */
        foreach(Locale::diffLocale($this->translate) as $locale)
        {
            $MaterialTransDTO = new Trans\MaterialTransDTO();
            $MaterialTransDTO->setLocal($locale);
            $this->addTranslate($MaterialTransDTO);
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

}
