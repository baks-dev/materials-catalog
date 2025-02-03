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

namespace BaksDev\Materials\Catalog\Entity;

use BaksDev\Core\Entity\EntityState;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Invariable\MaterialInvariableUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/* MaterialInvariable */

#[ORM\Entity]
#[ORM\Table(name: 'material_invariable')]
#[ORM\UniqueConstraint(columns: ['material', 'offer', 'variation', 'modification'])]
class MaterialInvariable extends EntityState
{
    /**
     * Идентификатор сущности
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: MaterialInvariableUid::TYPE)]
    private MaterialInvariableUid $id;

    /** ID сырья (не уникальное) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: MaterialUid::TYPE)]
    private MaterialUid $material;

    /** Константа ТП */
    #[ORM\Column(type: MaterialOfferConst::TYPE, nullable: true)]
    private ?MaterialOfferConst $offer = null;

    /** Константа множественного варианта */
    #[ORM\Column(type: MaterialVariationConst::TYPE, nullable: true)]
    private ?MaterialVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[ORM\Column(type: MaterialModificationConst::TYPE, nullable: true)]
    private ?MaterialModificationConst $modification = null;


    public function __construct()
    {
        $this->id = new MaterialInvariableUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): MaterialInvariableUid
    {
        return $this->id;
    }

    public function setEntity($dto): mixed
    {
        return parent::setEntity($dto);
    }
}