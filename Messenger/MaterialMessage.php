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

namespace BaksDev\Materials\Catalog\Messenger;

use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Products\Product\Type\Material\MaterialUid;

final class MaterialMessage
{
    /** Идентификатор */
    private string $id;

    /** Идентификатор события */
    private string $event;

    /** Идентификатор предыдущего события */
    private ?string $last;

    public function __construct(MaterialUid $id, MaterialEventUid $event, ?MaterialEventUid $last = null)
    {
        $this->id = (string) $id;
        $this->event = (string) $event;
        $this->last = $last ? (string) $last : null;
    }

    /** Идентификатор */
    public function getId(): MaterialUid
    {
        return new MaterialUid($this->id);
    }

    /** Идентификатор события */
    public function getEvent(): MaterialEventUid
    {
        return new MaterialEventUid($this->event);
    }

    /** Идентификатор предыдущего события */
    public function getLast(): ?MaterialEventUid
    {
        return $this->last ? new MaterialEventUid($this->last) : null;
    }
}
