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

namespace BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use BaksDev\Materials\Category\Type\Offers\Modification\CategoryMaterialModificationUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Вариант в торговом предложения */

#[ORM\Entity]
#[ORM\Table(name: 'material_modification')]
#[ORM\Index(columns: ['const'])]
#[ORM\Index(columns: ['article'])]
#[ORM\Index(columns: ['barcode'])]
class MaterialModification extends EntityEvent
{
    /** ID модификации множественного варианта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialModificationUid::TYPE)]
    private MaterialModificationUid $id;

    /** ID множественного варианта  */
    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: MaterialVariation::class, inversedBy: 'modification')]
    #[ORM\JoinColumn(name: 'variation', referencedColumnName: 'id')]
    private MaterialVariation $variation;

    /** Постоянный уникальный идентификатор модификации */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialModificationConst::TYPE)]
    private readonly MaterialModificationConst $const;

    /** Штрихкод */
    #[ORM\Column(type: MaterialBarcode::TYPE, nullable: true)]
    private ?MaterialBarcode $barcode = null;

    /** ID модификации категории */
    #[Assert\Uuid]
    #[ORM\Column(name: 'category_modification', type: CategoryMaterialModificationUid::TYPE, nullable: true)]
    private ?CategoryMaterialModificationUid $categoryModification = null;

    /** Заполненное значение */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    /** Артикул */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $article = null;

    /** Стоимость модификации */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: Price\MaterialModificationPrice::class, mappedBy: 'modification', cascade: ['all'], fetch: 'EAGER')]
    private ?Price\MaterialModificationPrice $price = null;

    /** Количественный учет */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: Quantity\MaterialModificationQuantity::class, mappedBy: 'modification', cascade: ['all'], fetch: 'EAGER')]
    private ?Quantity\MaterialModificationQuantity $quantity = null;

    /** Дополнительные фото модификации */
    #[Assert\Valid]
    #[ORM\OneToMany(targetEntity: Image\MaterialModificationImage::class, mappedBy: 'modification', cascade: ['all'], fetch: 'EAGER')]
    #[ORM\OrderBy(['root' => 'DESC'])]
    private Collection $image;

    public function __construct(MaterialVariation $variation)
    {
        $this->id = new MaterialModificationUid();
        $this->variation = $variation;
        $this->price = new Price\MaterialModificationPrice($this);
        $this->quantity = new Quantity\MaterialModificationQuantity($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): MaterialModificationUid
    {
        return $this->id;
    }

    public function getVariation(): MaterialVariation
    {
        return $this->variation;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Const.
     */
    public function getConst(): MaterialModificationConst
    {
        return $this->const;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialModificationInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialModificationInterface || $dto instanceof self)
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

    public function getBarcode(): ?MaterialBarcode
    {
        return $this->barcode;
    }
}
