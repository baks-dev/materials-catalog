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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation;

use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariationInterface;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Category\Type\Offers\Variation\CategoryMaterialVariationUid;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialVariation */
final class MaterialVariationCollectionDTO implements MaterialVariationInterface
{
    /** ID множественного варианта торгового предложения категории */
    private ?CategoryMaterialVariationUid $categoryVariation;

    /** Постоянный уникальный идентификатор варианта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly MaterialVariationConst $const;

    /** Штрихкод товара */
    private MaterialBarcode $barcode;

    /** Заполненное значение */
    private ?string $value = null;

    /** Артикул */
    private ?string $article = null;

    /** Стоимость торгового предложения */
    #[Assert\Valid]
    private ?Price\MaterialVariationPriceDTO $price = null;

    /** Количественный учет */
    #[Assert\Valid]
    private Quantity\MaterialsVariationQuantityDTO $quantity;

    /** Дополнительные фото торгового предложения */
    #[Assert\Valid]
    private ArrayCollection $image;

    /** Модификации множественных вариантов */
    #[Assert\Valid]
    private ArrayCollection $modification;


    public function __construct()
    {
        $this->image = new ArrayCollection();
        $this->modification = new ArrayCollection();
        $this->quantity = new Quantity\MaterialsVariationQuantityDTO();
    }


    /** Постоянный уникальный идентификатор варианта */
    public function getConst(): MaterialVariationConst
    {
        if(!(new ReflectionProperty(self::class, 'const'))->isInitialized($this))
        {
            $this->const = new MaterialVariationConst();

            if(false === (new ReflectionProperty(self::class, 'barcode')->isInitialized($this)))
            {
                $this->barcode = new MaterialBarcode(MaterialBarcode::generate());
            }
        }

        return $this->const;
    }


    public function setConst(MaterialVariationConst $const): void
    {
        if(!(new ReflectionProperty(self::class, 'const'))->isInitialized($this))
        {
            $this->const = $const;
        }
    }

    /**
     * Barcode
     */
    public function getBarcode(): MaterialBarcode
    {
        if(false === (new ReflectionProperty(self::class, 'barcode')->isInitialized($this)))
        {
            $this->barcode = new MaterialBarcode(MaterialBarcode::generate());
        }

        return $this->barcode;
    }

    public function setBarcode(?MaterialBarcode $barcode): self
    {
        if(is_null($barcode))
        {
            $barcode = new MaterialBarcode(MaterialBarcode::generate());
        }

        $this->barcode = $barcode;
        return $this;
    }

    /** Заполненное значение */

    public function getValue(): ?string
    {
        return $this->value;
    }


    public function setValue(?string $value): void
    {
        $this->value = $value;
    }


    /** Артикул */

    public function getArticle(): ?string
    {
        return $this->article;
    }


    public function setArticle(?string $article): void
    {
        $this->article = $article;
    }


    /** Стоимость торгового предложения */

    public function getPrice(): ?Price\MaterialVariationPriceDTO
    {
        return $this->price;
    }


    public function setPrice(?Price\MaterialVariationPriceDTO $price): void
    {
        $this->price = $price;
    }


    /** Количественный учет */

    public function getQuantity(): Quantity\MaterialsVariationQuantityDTO
    {
        return $this->quantity;
    }

    /** Дополнительные фото торгового предложения */

    public function getImage(): ArrayCollection
    {
        return $this->image;
    }


    public function addImage(Image\MaterialVariationImageCollectionDTO $image): void
    {
        $filter = $this->image->filter(function(Image\MaterialVariationImageCollectionDTO $element) use ($image) {
            return !$image->file && $image->getName() === $element->getName();
        });

        if($filter->isEmpty())
        {
            $this->image->add($image);
        }
    }


    public function removeImage(Image\MaterialVariationImageCollectionDTO $image): void
    {
        $this->image->removeElement($image);
    }


    /** Модификации множественных вариантов */

    public function getModification(): ArrayCollection
    {
        return $this->modification;
    }


    public function addModification(Modification\MaterialModificationCollectionDTO $modification): void
    {
        if(!$this->modification->contains($modification))
        {
            $this->modification->add($modification);
        }
    }


    public function removeModification(Modification\MaterialModificationCollectionDTO $modification): void
    {
        $this->modification->removeElement($modification);
    }


    /** ID множественного варианта торгового предложения категории */

    public function getCategoryVariation(): ?CategoryMaterialVariationUid
    {
        return $this->categoryVariation;
    }


    public function setCategoryVariation(?CategoryMaterialVariationUid $categoryVariation): void
    {
        $this->categoryVariation = $categoryVariation;
    }

}
