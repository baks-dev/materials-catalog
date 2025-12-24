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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers;

use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffersInterface;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Category\Type\Offers\Id\CategoryMaterialOffersUid;
use BaksDev\Products\Product\Type\Barcode\ProductBarcode;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialOffer */
final class MaterialOffersCollectionDTO implements MaterialOffersInterface
{
    /** ID торгового предложения категории */
    private ?CategoryMaterialOffersUid $categoryOffer;

    /** Постоянный уникальный идентификатор ТП */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly MaterialOfferConst $const;

    /** Штрихкод товара */
    private MaterialBarcode $barcode;

    /** Заполненное значение */
    private ?string $value = null;

    /** Артикул */
    private ?string $article = null;


    /** Стоимость торгового предложения */
    #[Assert\Valid]
    private ?Price\MaterialOfferPriceDTO $price = null;

    /** Количественный учет */
    #[Assert\Valid]
    private Quantity\MaterialOfferQuantityDTO $quantity;

    /** Дополнительные фото торгового предложения */
    #[Assert\Valid]
    private ArrayCollection $image;

    /** Коллекция вариаций в торговом предложении  */
    #[Assert\Valid]
    private ArrayCollection $variation;


    public function __construct()
    {
        $this->image = new ArrayCollection();
        $this->variation = new ArrayCollection();
        $this->quantity = new Quantity\MaterialOfferQuantityDTO();
    }


    /** Постоянный уникальный идентификатор ТП */

    public function getConst(): MaterialOfferConst
    {
        if(!(new ReflectionProperty(self::class, 'const'))->isInitialized($this))
        {
            $this->const = new MaterialOfferConst();

            if(false === (new ReflectionProperty(self::class, 'barcode')->isInitialized($this)))
            {
                $this->barcode = new MaterialBarcode(MaterialBarcode::generate());
            }
        }

        return $this->const;
    }

    public function setConst(MaterialOfferConst $const): void
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

    public function getPrice(): ?Price\MaterialOfferPriceDTO
    {
        return $this->price;
    }

    public function setPrice(?Price\MaterialOfferPriceDTO $price): void
    {
        $this->price = $price;
    }

    /** Количественный учет */

    public function getQuantity(): Quantity\MaterialOfferQuantityDTO
    {
        return $this->quantity;
    }

    /** Дополнительные фото торгового предложения */

    public function getImage(): ArrayCollection
    {
        return $this->image;
    }

    public function addImage(Image\MaterialOfferImageCollectionDTO $image): void
    {

        $filter = $this->image->filter(function(Image\MaterialOfferImageCollectionDTO $element) use ($image) {
            return !$image->file && $image->getName() === $element->getName();
        });

        if($filter->isEmpty())
        {
            $this->image->add($image);

        }


    }

    public function removeImage(Image\MaterialOfferImageCollectionDTO $image): void
    {
        $this->image->removeElement($image);
    }

    /** Коллекция торговых предложений */

    public function getVariation(): ArrayCollection
    {
        return $this->variation;
    }

    public function addVariation(Variation\MaterialVariationCollectionDTO $variation): void
    {

        $filter = $this->variation->filter(function(Variation\MaterialVariationCollectionDTO $element) use (
            $variation
        ) {
            return $variation->getValue() === $element->getValue() && $variation->getBarcode() === $element->getBarcode();
        });

        if($filter->isEmpty())
        {
            $this->variation->add($variation);
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

    public function removeVariation(Variation\MaterialVariationCollectionDTO $variation): void
    {
        $this->variation->removeElement($variation);
    }


    /** ID торгового предложения категории */
    public function getCategoryOffer(): ?CategoryMaterialOffersUid
    {
        return $this->categoryOffer;
    }


    public function setCategoryOffer(?CategoryMaterialOffersUid $categoryOffer): void
    {
        $this->categoryOffer = $categoryOffer;
    }


}
