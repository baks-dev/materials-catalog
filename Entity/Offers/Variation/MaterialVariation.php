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

namespace BaksDev\Materials\Catalog\Entity\Offers\Variation;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Category\Type\Offers\Variation\CategoryMaterialVariationUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// Вариант в торговом предложения

#[ORM\Entity]
#[ORM\Table(name: 'material_variation')]
#[ORM\Index(columns: ['const'])]
#[ORM\Index(columns: ['article'])]
class MaterialVariation extends EntityEvent
{
    /** ID варианта торгового предложения */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialVariationUid::TYPE)]
    private MaterialVariationUid $id;

    /** ID торгового предложения  */
    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: MaterialOffer::class, inversedBy: 'variation')]
    #[ORM\JoinColumn(name: 'offer', referencedColumnName: 'id')]
    private MaterialOffer $offer;

    /** Постоянный уникальный идентификатор варианта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialVariationConst::TYPE)]
    private readonly MaterialVariationConst $const;

    /** ID торгового предложения категории */
    #[Assert\Uuid]
    #[ORM\Column(name: 'category_variation', type: CategoryMaterialVariationUid::TYPE, nullable: true)]
    private ?CategoryMaterialVariationUid $categoryVariation = null;

    /** Заполненное значение */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    /** Артикул */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $article = null;

    /** Стоимость торгового предложения */
    #[ORM\OneToOne(targetEntity: Price\MaterialVariationPrice::class, mappedBy: 'variation', cascade: ['all'], fetch: 'EAGER')]
    private ?Price\MaterialVariationPrice $price = null;

    /** Количественный учет */
    #[ORM\OneToOne(targetEntity: Quantity\MaterialsVariationQuantity::class, mappedBy: 'variation', cascade: ['all'], fetch: 'EAGER')]
    private ?Quantity\MaterialsVariationQuantity $quantity = null;

    /** Дополнительные фото торгового предложения */
    #[Assert\Valid]
    #[ORM\OneToMany(targetEntity: Image\MaterialVariationImage::class, mappedBy: 'variation', cascade: ['all'], fetch: 'EAGER')]
    #[ORM\OrderBy(['root' => 'DESC'])]
    private Collection $image;

    /** Коллекция вариаций в торговом предложении  */
    #[Assert\Valid]
    #[ORM\OrderBy(['value' => 'ASC'])]
    #[ORM\OneToMany(targetEntity: Modification\MaterialModification::class, mappedBy: 'variation', cascade: ['all'], fetch: 'EAGER')]
    private Collection $modification;

    public function __construct(MaterialOffer $offer)
    {
        $this->id = new MaterialVariationUid();
        $this->offer = $offer;
        $this->price = new Price\MaterialVariationPrice($this);
        $this->quantity = new Quantity\MaterialsVariationQuantity($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): MaterialVariationUid
    {
        return $this->id;
    }

    public function getOffer(): MaterialOffer
    {
        return $this->offer;
    }

    /**
     * Const.
     */
    public function getConst(): MaterialVariationConst
    {
        return $this->const;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialVariationInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialVariationInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Image
     */
    public function getImage(): Collection
    {
        return $this->image;
    }

    /**
     * Modification
     */
    public function getModification(): Collection
    {
        return $this->modification;
    }

}
