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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation\Modification\Image;

use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Image\MaterialModificationImageInterface;
use Symfony\Component\HttpFoundation\File\File;

/** @see MaterialModificationImage */
final class MaterialModificationImageCollectionDTO implements MaterialModificationImageInterface
{
    public ?File $file = null;

    /** Название файла */
    private ?string $name = null;

    /** Расширение */
    private ?string $ext = null;

    /** Флаг загрузки CDN */
    private bool $cdn = false;

    /** Главное фото */
    private bool $root = false;

    /** Размер файла */
    private ?int $size = null;


    /** Название файла */

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }


    /** Расширение */

    public function getExt(): ?string
    {
        return $this->ext;
    }

    public function setExt(?string $ext): self
    {
        $this->ext = $ext;
        return $this;
    }


    /** Флаг загрузки CDN */

    public function getCdn(): bool
    {
        return $this->cdn;
    }


    /** Главное фото */

    public function getRoot(): bool
    {
        return $this->root;
    }


    public function setRoot(bool $root): void
    {
        $this->root = $root;
    }


    /** Размер файла */

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

}
