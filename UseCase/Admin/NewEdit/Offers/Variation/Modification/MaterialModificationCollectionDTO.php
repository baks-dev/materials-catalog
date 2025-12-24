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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification;

use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModificationInterface;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Category\Type\Offers\Modification\CategoryMaterialModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialModification */
final class MaterialModificationCollectionDTO implements MaterialModificationInterface
{
    /** ID множественного варианта торгового предложения категории */
    private CategoryMaterialModificationUid $categoryModification;

    /** Постоянный уникальный идентификатор модификации */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly MaterialModificationConst $const;

    /** Штрихкод товара */
    private MaterialBarcode $barcode;

    /** Заполненное значение */
    private ?string $value = null;

    /** Артикул */
    private ?string $article = null;

    /** Стоимость торгового предложения */
    #[Assert\Valid]
    private ?Price\MaterialModificationPriceDTO $price = null;

    /** Количественный учет */
    #[Assert\Valid]
    private Quantity\MaterialModificationQuantityDTO $quantity;

    /** Дополнительные фото торгового предложения */
    #[Assert\Valid]
    private ArrayCollection $image;


    public function __construct()
    {
        $this->image = new ArrayCollection();
        $this->quantity = new Quantity\MaterialModificationQuantityDTO();
    }


    /** Постоянный уникальный идентификатор модификации */

    public function getConst(): MaterialModificationConst
    {
        if(!(new ReflectionProperty(self::class, 'const'))->isInitialized($this))
        {
            $this->const = new MaterialModificationConst();

            if(false === (new ReflectionProperty(self::class, 'barcode')->isInitialized($this)))
            {
                $this->barcode = new MaterialBarcode(MaterialBarcode::generate());
            }
        }

        return $this->const;
    }

    public function setConst(MaterialModificationConst $const): void
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

    public function getPrice(): ?Price\MaterialModificationPriceDTO
    {
        return $this->price;
    }


    public function setPrice(?Price\MaterialModificationPriceDTO $price): void
    {
        $this->price = $price;
    }


    /** Количественный учет */

    public function getQuantity(): Quantity\MaterialModificationQuantityDTO
    {
        return $this->quantity;
    }

    /** Дополнительные фото торгового предложения */

    public function getImage(): ArrayCollection
    {
        return $this->image;
    }


    public function addImage(Image\MaterialModificationImageCollectionDTO $image): void
    {
        $filter = $this->image->filter(function(Image\MaterialModificationImageCollectionDTO $element) use ($image) {
            return !$image->file && $image->getName() === $element->getName();
        });

        if($filter->isEmpty())
        {
            $this->image->add($image);
        }
    }


    public function removeImage(Image\MaterialModificationImageCollectionDTO $image): void
    {
        $this->image->removeElement($image);
    }

    public function getCategoryModification(): CategoryMaterialModificationUid
    {
        return $this->categoryModification;
    }

    public function setCategoryModification(CategoryMaterialModificationUid $categoryModification): void
    {
        $this->categoryModification = $categoryModification;
    }

}
