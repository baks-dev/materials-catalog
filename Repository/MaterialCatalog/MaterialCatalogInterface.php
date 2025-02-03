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

namespace BaksDev\Materials\Catalog\Repository\MaterialCatalog;

use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;

interface MaterialCatalogInterface
{
    /**
     * Максимальное количество записей в результате
     */
    public function maxResult(int $max): self;

    /**
     * Фильтр по категории
     */
    public function forCategory(CategoryMaterial|CategoryMaterialUid|string $category): self;

    /**
     * Метод возвращает ограниченный по количеству элементов список сырья из разных категорий
     *
     * @return array{
     * "id": string,
     * "event": string,
     * "material_name": string,
     * "url": string,
     * "active_from": string,
     * "active_to": string,
     * "material_offer_uid": string,
     * "material_offer_value": string,
     * "material_offer_reference": string,
     * "material_variation_uid": string,
     * "material_variation_value": string,
     * "material_variation_reference": string,
     * "material_modification_uid": string,
     * "material_modification_value": string,
     * "material_modification_reference": string,
     * "material_article": string,
     * "material_image": string,
     * "material_image_ext": string,
     * "material_image_cdn": bool,
     * "material_price": int,
     * "material_currency": string,
     * "category_url": string,
     * "category_name": string,
     * "category_section_field": string,
     * } | false
     */
    public function find(): array|false;
}