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

namespace BaksDev\Materials\Catalog\UseCase\Admin\Rename;

use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEventInterface;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialEvent */
final class RenameMaterialDTO implements MaterialEventInterface
{
    /** Идентификатор */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private ?MaterialEventUid $id = null;

    #[Assert\Valid]
    private ArrayCollection $translate;


    public function __construct()
    {
        $this->translate = new ArrayCollection();
    }

    public function getEvent(): ?MaterialEventUid
    {
        return $this->id;
    }

    public function setId(MaterialEventUid $id): void
    {
        $this->id = $id;
    }


    /* TRANS */

    public function getTranslate(): ArrayCollection
    {

        /* Вычисляем расхождение и добавляем неопределенные локали */
        foreach(Locale::diffLocale($this->translate) as $locale)
        {
            $MaterialTransDTO = new Trans\MaterialTransDTO();
            $MaterialTransDTO->setLocal($locale);
            $this->addTranslate($MaterialTransDTO);
        }

        return $this->translate;
    }

    public function setTranslate(ArrayCollection $trans): void
    {
        $this->translate = $trans;
    }

    public function addTranslate(Trans\MaterialTransDTO $trans): void
    {
        if(empty($trans->getLocal()->getLocalValue()))
        {
            return;
        }

        if(!$this->translate->contains($trans))
        {
            $this->translate->add($trans);
        }
    }
}
