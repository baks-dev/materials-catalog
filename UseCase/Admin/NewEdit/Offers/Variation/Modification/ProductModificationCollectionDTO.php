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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification;

use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\ProductModificationInterface;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Category\Type\Offers\Modification\CategoryProductModificationUid;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductModification */
final class ProductModificationCollectionDTO implements ProductModificationInterface
{
    /** ID множественного варианта торгового предложения категории */
    private CategoryProductModificationUid $categoryModification;

    /** Постоянный уникальный идентификатор модификации */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly ProductModificationConst $const;

    /** Заполненное значение */
    private ?string $value = null;

    /** Артикул */
    private ?string $article = null;

    /** Постфикс */
    private ?string $postfix = null;

    /** Стоимость торгового предложения */
    #[Assert\Valid]
    private ?Price\ProductModificationPriceDTO $price = null;

    /** Количественный учет */
    #[Assert\Valid]
    private Quantity\ProductModificationQuantityDTO $quantity;

    /** Дополнительные фото торгового предложения */
    #[Assert\Valid]
    private ArrayCollection $image;


    public function __construct()
    {
        $this->image = new ArrayCollection();
        $this->quantity = new Quantity\ProductModificationQuantityDTO();
    }


    /** Постоянный уникальный идентификатор модификации */

    public function getConst(): ProductModificationConst
    {
        if(!(new ReflectionProperty(self::class, 'const'))->isInitialized($this))
        {
            $this->const = new ProductModificationConst();
        }

        return $this->const;
    }

    public function setConst(ProductModificationConst $const): void
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

    public function getPrice(): ?Price\ProductModificationPriceDTO
    {
        return $this->price;
    }


    public function setPrice(?Price\ProductModificationPriceDTO $price): void
    {
        $this->price = $price;
    }


    /** Количественный учет */

    public function getQuantity(): Quantity\ProductModificationQuantityDTO
    {
        return $this->quantity;
    }

    /** Дополнительные фото торгового предложения */

    public function getImage(): ArrayCollection
    {
        return $this->image;
    }


    public function addImage(Image\ProductModificationImageCollectionDTO $image): void
    {
        $filter = $this->image->filter(function(Image\ProductModificationImageCollectionDTO $element) use ($image) {
            return !$image->file && $image->getName() === $element->getName();
        });

        if($filter->isEmpty())
        {
            $this->image->add($image);
        }
    }


    public function removeImage(Image\ProductModificationImageCollectionDTO $image): void
    {
        $this->image->removeElement($image);
    }

    public function getCategoryModification(): CategoryProductModificationUid
    {
        return $this->categoryModification;
    }

    public function setCategoryModification(CategoryProductModificationUid $categoryModification): void
    {
        $this->categoryModification = $categoryModification;
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
