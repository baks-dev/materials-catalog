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

namespace BaksDev\Materials\Catalog\Type\Barcode;

use BaksDev\Core\Type\UidType\Uid;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class MaterialBarcode
{
    public const string TYPE = 'material_barcode';

    private string $value;

    public function __construct(?string $value = null)
    {
        /** Делаем проверку строки, не передан ли код маркировки «Честный знак» */
        preg_match_all('/\((\d{2})\)((?:(?!\(\d{2}\)).)*)/', $value, $matches, PREG_SET_ORDER);

        if(count($matches) === 4 && isset($matches[0][2]))
        {
            $value = $matches[0][2];
        }

        if($value)
        {
            $this->value = $value;
            return;
        }

        $this->value = $this->barcode(self::generate());
    }

    public function barcode(string $article): string
    {
        // Generate a random alphanumeric string
        $article = str_replace('-', '', $article);
        $article = strrev($article);

        $length = 10;
        $barcode = '460'; // 460 1089289353

        for($i = 0; $i < $length; $i++)
        {
            $char = mb_substr($article, $i, 1);
            $index = ord($char) % 10;
            $barcode .= $index;
        }

        return $barcode;
    }

    public static function generate(Uid|Uuid|string|null $uuid = null): string
    {
        if(is_null($uuid))
        {
            usleep(12000);
            $uuid = new UuidV7();
        }

        if(is_string($uuid))
        {
            $uuid = new UuidV7($uuid);
        }

        if($uuid instanceof Uid)
        {
            $uuid = $uuid->getValue();
        }

        if($uuid instanceof Uuid)
        {
            $uuid = $uuid->toString();
        }

        $uid = new UuidV7($uuid);

        $low = explode('-', $uuid, 2);
        $currentLow = current($low);

        $mid = explode('-', end($low), 2);
        $currentMid = current($mid);

        $res = filter_var($currentLow.$currentMid, FILTER_SANITIZE_NUMBER_INT);
        $res = ltrim($res, '0');

        $rtrim = rtrim($uid->getDateTime()->getTimestamp().$uid->getDateTime()->format('u'), '0');

        $pre = '2'.substr($rtrim, 1).$res;

        $barcode = substr($pre, 0, 13);

        /** Делаем проверку уникальности штрихкода */

        $cache = new FilesystemAdapter('materials-catalog');

        $count = 0;

        while(true)
        {
            $item = $cache->getItem('barcode-'.$barcode);

            /** Присваиваем штрихкод если НЕ найден */
            if($item->isHit() === false)
            {
                $item->set($uuid);
                $cache->save($item);
                break;
            }

            /** Присваиваем штрихкод если найден и совпадает в uid */
            if($item->get() === $uuid)
            {
                break;
            }

            /** Генерируем новый штрихкод на +1 */
            $barcode = substr_replace($barcode, '40'.$count, 0, 3);
            $count++;
        }

        return $barcode;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(mixed $value): bool
    {
        return $this->value === (string) $value;
    }
}
