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

namespace BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId;

use App\Kernel;
use BaksDev\Core\Type\UidType\Uid;
use Symfony\Component\Uid\AbstractUid;

final class MaterialVariationConst extends Uid
{
    public const string TEST = 'ccdfb476-cbf7-7cec-b905-87ab3804b290';

    public const string TYPE = 'material_variation_const';


    private mixed $attr;

    private mixed $option;

    private mixed $property;

    private mixed $characteristic;

    private mixed $reference;


    /*
       $dbal->addSelect(' AS value');
       $dbal->addSelect(' AS attr');
       $dbal->addSelect(' AS option');
       $dbal->addSelect(' AS property');
       $dbal->addSelect(' AS characteristic');
       $dbal->addSelect(' AS reference');
*/
    public function __construct(
        AbstractUid|self|string|null $value = null,
        mixed $attr = null,
        mixed $option = null,
        mixed $property = null,
        mixed $characteristic = null,
        mixed $reference = null,
    )
    {

        /**
         * Добавляем задержка выполнения для генератора Brcode
         * @see MaterialBarcode
         */
        if(is_null($value))
        {
            usleep(12000);
        }

        parent::__construct($value);

        $this->attr = $attr;
        $this->option = $option;
        $this->property = $property;
        $this->characteristic = $characteristic;
        $this->reference = $reference;
    }

    public function getAttr(): mixed
    {
        return $this->attr;
    }

    public function getOption(): mixed
    {
        return $this->option;
    }

    public function getProperty(): mixed
    {
        return $this->property;
    }

    public function getCharacteristic(): mixed
    {
        return $this->characteristic;
    }

    /**
     * Reference
     */
    public function getReference(): mixed
    {
        return $this->reference;
    }


}
