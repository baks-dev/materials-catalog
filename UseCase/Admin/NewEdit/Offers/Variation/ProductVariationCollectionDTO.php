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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation;

use BaksDev\Materials\Catalog\Entity\Offers\Variation\ProductVariationInterface;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Category\Type\Offers\Variation\CategoryProductVariationUid;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductVariation */
final class ProductVariationCollectionDTO implements ProductVariationInterface
{
    /** ID множественного варианта торгового предложения категории */
    private ?CategoryProductVariationUid $categoryVariation;

    /** Постоянный уникальный идентификатор варианта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly ProductVariationConst $const;

    /** Заполненное значение */
    private ?string $value = null;

    /** Артикул */
    private ?string $article = null;

    /** Постфикс */
    private ?string $postfix = null;

    /** Стоимость торгового предложения */
    #[Assert\Valid]
    private ?Price\ProductVariationPriceDTO $price = null;

    /** Количественный учет */
    #[Assert\Valid]
    private Quantity\ProductVariationQuantityDTO $quantity;

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
        $this->quantity = new Quantity\ProductVariationQuantityDTO();
    }


    /** Постоянный уникальный идентификатор варианта */
    public function getConst(): ProductVariationConst
    {
        if(!(new ReflectionProperty(self::class, 'const'))->isInitialized($this))
        {
            $this->const = new ProductVariationConst();
        }

        return $this->const;
    }


    public function setConst(ProductVariationConst $const): void
    {
        if(!(new ReflectionProperty(self::class, 'const'))->isInitialized($this))
        {
            $this->const = $const;
        }
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

    public function getPrice(): ?Price\ProductVariationPriceDTO
    {
        return $this->price;
    }


    public function setPrice(?Price\ProductVariationPriceDTO $price): void
    {
        $this->price = $price;
    }


    /** Количественный учет */

    public function getQuantity(): Quantity\ProductVariationQuantityDTO
    {
        return $this->quantity;
    }

    /** Дополнительные фото торгового предложения */

    public function getImage(): ArrayCollection
    {
        return $this->image;
    }


    public function addImage(Image\ProductVariationImageCollectionDTO $image): void
    {
        $filter = $this->image->filter(function(Image\ProductVariationImageCollectionDTO $element) use ($image) {
            return !$image->file && $image->getName() === $element->getName();
        });

        if($filter->isEmpty())
        {
            $this->image->add($image);
        }
    }


    public function removeImage(Image\ProductVariationImageCollectionDTO $image): void
    {
        $this->image->removeElement($image);
    }


    /** Модификации множественных вариантов */

    public function getModification(): ArrayCollection
    {
        return $this->modification;
    }


    public function addModification(Modification\ProductModificationCollectionDTO $modification): void
    {
        if(!$this->modification->contains($modification))
        {
            $this->modification->add($modification);
        }
    }


    public function removeModification(Modification\ProductModificationCollectionDTO $modification): void
    {
        $this->modification->removeElement($modification);
    }


    /** ID множественного варианта торгового предложения категории */

    public function getCategoryVariation(): ?CategoryProductVariationUid
    {
        return $this->categoryVariation;
    }


    public function setCategoryVariation(?CategoryProductVariationUid $categoryVariation): void
    {
        $this->categoryVariation = $categoryVariation;
    }


    /** Постфикс */

    public function getPostfix(): ?string
    {
        return $this->postfix;
    }


    public function setPostfix(?string $postfix): void
    {
        $this->postfix = $postfix;
    }

}
