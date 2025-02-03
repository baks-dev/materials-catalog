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

namespace BaksDev\Materials\Catalog\Repository\MaterialChoice;

use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use Generator;

interface MaterialChoiceInterface
{
    /**
     * Метод возвращает все идентификаторы сырья (MaterialUid) с названием
     */
    public function findAll(CategoryMaterialUid|false $category = false): array|false;

    /**
     * Метод возвращает активные идентификаторы событий (MaterialEventUid) сырья
     */
    public function fetchAllMaterialEvent(): ?array;

    /**
     * Метод возвращает идентификаторы событий (MaterialEventUid) доступной для продажи сырья
     */
    public function fetchAllMaterialEventByExists(CategoryMaterialUid|false $category = false): Generator;
}
