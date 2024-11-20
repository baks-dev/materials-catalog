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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Property;

use BaksDev\Materials\Catalog\Entity\Property\ProductPropertyInterface;
use BaksDev\Products\Category\Type\Section\Field\Id\CategoryProductSectionFieldUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductProperty */
final class PropertyCollectionDTO implements ProductPropertyInterface
{
    /** Связь на поле из категории */
    #[Assert\Uuid]
    private ?CategoryProductSectionFieldUid $field = null;

    /** Заполненное значение */
    private ?string $value = null;

    /* Вспомогательные свойства */
    private ?string $section = null;

    /* Сортировка полей в форме */
    private int $sort = 0;


    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }


    /**
     * @param string|null $value
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }


    public function getField(): ?CategoryProductSectionFieldUid
    {
        return $this->field;
    }


    public function setField(CategoryProductSectionFieldUid $field): void
    {
        $this->field = $field;
    }

    /**
     * @return string|null
     */
    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }


    /* Сортировка полей в форме */

    public function getSort(): int
    {
        return $this->sort;
    }


    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }


}
