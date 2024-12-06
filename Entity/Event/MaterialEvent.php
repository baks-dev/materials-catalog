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

namespace BaksDev\Materials\Catalog\Entity\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Materials\Catalog\Entity\Category\ProductCategory;
use BaksDev\Materials\Catalog\Entity\Cover\MaterialCover;
use BaksDev\Materials\Catalog\Entity\Info\ProductInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Modify\MaterialModify;
use BaksDev\Materials\Catalog\Entity\Price\ProductPrice;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Category\Type\Id\CategoryProductUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// События Product

#[ORM\Entity]
#[ORM\Table(name: 'material_event')]
class MaterialEvent extends EntityEvent
{
    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialEventUid::TYPE)]
    private MaterialEventUid $id;

    /** ID Product */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialUid::TYPE, nullable: false)]
    private ?MaterialUid $main = null;

    /** Модификатор */
    #[ORM\OneToOne(targetEntity: MaterialModify::class, mappedBy: 'event', cascade: ['all'])]
    private MaterialModify $modify;

    /** Перевод */
    #[Assert\Valid]
    #[ORM\OneToMany(targetEntity: MaterialTrans::class, mappedBy: 'event', cascade: ['all'])]
    private Collection $translate;

    /** Cover */
    #[ORM\OneToOne(targetEntity: MaterialCover::class, mappedBy: 'event', cascade: ['all'])]
    private ?MaterialCover $cover = null;


    public function __construct()
    {
        $this->id = new MaterialEventUid();
        $this->modify = new MaterialModify($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getMain(): ?MaterialUid
    {
        return $this->main;
    }

    public function setMain(MaterialUid|Material $main): void
    {
        $this->main = $main instanceof Material ? $main->getId() : $main;
    }

    public function getId(): MaterialEventUid
    {
        return $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getNameByLocale(Locale $locale): ?string
    {
        $name = null;

        /** @var MaterialTrans $trans */
        foreach($this->translate as $trans)
        {
            if($name = $trans->getNameByLocal($locale))
            {
                break;
            }
        }

        return $name;
    }


    /**
     * Метод возвращает идентификатор корневой категории продукта
     */
    public function getRootCategory(): ?CategoryProductUid
    {
        $filter = $this->category->filter(function(ProductCategory $category) {
            return $category->isRoot();

        });

        if($filter->isEmpty())
        {
            return null;
        }

        return $filter->current()->getCategory();
    }

    public function getCategory(): Collection
    {
        return $this->category;
    }

    public function getOffer(): Collection
    {
        return $this->offer;
    }

    /**
     * Photo
     */
    public function getPhoto(): Collection
    {
        return $this->photo;
    }

    /**
     * File
     */
    public function getFile(): Collection
    {
        return $this->file;
    }

    /**
     * Video
     */
    public function getVideo(): Collection
    {
        return $this->video;
    }

    /**
     * Info
     */
    public function getInfo(): ProductInfo
    {
        return $this->info;
    }


}
