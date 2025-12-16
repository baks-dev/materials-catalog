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

namespace BaksDev\Materials\Catalog\Entity\Offers;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Category\Type\Offers\Id\CategoryMaterialOffersUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

// Торговые предложения

#[ORM\Entity()]
#[ORM\Table(name: 'material_offer')]
#[ORM\Index(columns: ['const'])]
#[ORM\Index(columns: ['article'])]
#[ORM\Index(columns: ['barcode'])]
class MaterialOffer extends EntityEvent
{
    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialOfferUid::TYPE)]
    private MaterialOfferUid $id;

    /** ID события */
    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: MaterialEvent::class, inversedBy: 'offer')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private MaterialEvent $event;

    /** ID торгового предложения категории */
    #[Assert\Uuid]
    #[Assert\Type(CategoryMaterialOffersUid::class)]
    #[ORM\Column(name: 'category_offer', type: CategoryMaterialOffersUid::TYPE, nullable: true)]
    private ?CategoryMaterialOffersUid $categoryOffer = null;

    /** Постоянный уникальный идентификатор ТП */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialOfferConst::TYPE)]
    private readonly MaterialOfferConst $const;

    /** Штрихкод */
    #[ORM\Column(type: MaterialBarcode::TYPE, nullable: true)]
    private ?MaterialBarcode $barcode = null;

    /** Заполненное значение */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    /** Артикул */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $article = null;

    /** Стоимость торгового предложения */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: Price\MaterialOfferPrice::class, mappedBy: 'offer', cascade: ['all'], fetch: 'EAGER')]
    private ?Price\MaterialOfferPrice $price = null;

    /** Количественный учет */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: Quantity\MaterialOfferQuantity::class, mappedBy: 'offer', cascade: ['all'], fetch: 'EAGER')]
    private ?Quantity\MaterialOfferQuantity $quantity = null;

    /** Дополнительные фото торгового предложения */
    #[Assert\Valid]
    #[ORM\OneToMany(targetEntity: Image\MaterialOfferImage::class, mappedBy: 'offer', cascade: ['all'], fetch: 'EAGER')]
    #[ORM\OrderBy(['root' => 'DESC'])]
    private Collection $image;

    /** Коллекция вариаций в торговом предложении  */
    #[Assert\Valid]
    #[ORM\OrderBy(['value' => 'ASC'])]
    #[ORM\OneToMany(targetEntity: Variation\MaterialVariation::class, mappedBy: 'offer', cascade: ['all'], fetch: 'EAGER')]
    private Collection $variation;

    public function __construct(MaterialEvent $event)
    {
        $this->event = $event;
        $this->id = new MaterialOfferUid();
        $this->price = new Price\MaterialOfferPrice($this);
        $this->quantity = new Quantity\MaterialOfferQuantity($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): MaterialOfferUid
    {
        return $this->id;
    }

    /**
     * Const.
     */
    public function getConst(): MaterialOfferConst
    {
        return $this->const;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof MaterialOffersInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof MaterialOffersInterface || $dto instanceof self)
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
     * Variation
     */
    public function getVariation(): Collection
    {
        return $this->variation;
    }

    public function getBarcode(): ?MaterialBarcode
    {
        return $this->barcode;
    }
}
