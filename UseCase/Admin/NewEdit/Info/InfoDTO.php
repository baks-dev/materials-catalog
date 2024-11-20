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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Info;

use BaksDev\Materials\Catalog\Entity\Info\ProductInfoInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductInfo */
final class InfoDTO implements ProductInfoInterface
{
    /** Семантическая ссылка на товар (строка с тире и нижним подчеркиванием) */
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[a-z0-9\_\-]+$/i'
    )]
    private string $url;

    /** Артикул товара */
    private ?string $article = null;

    /** Профиль пользователя */
    #[Assert\Uuid]
    private ?UserProfileUid $profile = null;

    /** Сортировка */
    private int $sort = 500;


    /** Семантическая ссылка на товар */

    public function getUrl(): string
    {
        return $this->url;
    }


    public function setUrl(string $url): void
    {
        $this->url = $url;
    }


    public function updateUrlUniq(): void
    {
        $this->url = uniqid($this->url.'_', false);
    }


    /** Артикул товара */

    public function getArticle(): ?string
    {
        return $this->article;
    }


    public function setArticle(?string $article): void
    {
        $this->article = $article;
    }


    /** Профиль пользователя */

    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }


    public function setProfile(?UserProfileUid $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * Sort
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;
        return $this;
    }
}
