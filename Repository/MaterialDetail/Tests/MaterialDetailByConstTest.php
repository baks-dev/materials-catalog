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

namespace BaksDev\Materials\Catalog\Repository\MaterialDetail\Tests;

use BaksDev\Materials\Catalog\Repository\MaterialDetail\MaterialDetailByConstInterface;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group materials-catalog
 */
#[When(env: 'test')]
class MaterialDetailByConstTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var MaterialDetailByConstInterface $OneMaterialDetailByConst */
        $OneMaterialDetailByConst = self::getContainer()->get(MaterialDetailByConstInterface::class);

        $current = $OneMaterialDetailByConst
            ->material(new MaterialUid('01876b34-ed23-7c18-ba48-9071e8646a08'))
            ->offerConst(new MaterialOfferConst('01876b34-eccb-7188-887f-0738cae05232'))
            ->variationConst(new MaterialVariationConst('01876b34-ecce-7c46-9f63-fc184b6527ee'))
            ->modificationConst(new MaterialModificationConst('01876b34-ecd2-762c-9834-b6a914a020ba'))
            ->find();


        $array_keys = [
            "id",
            "event",
            "active",
            'active_from',
            'active_to',
            "material_name",
            'material_preview',
            'material_description',
            "material_url",
            "material_offer_uid",
            "material_offer_const",
            "material_offer_value",
            "material_offer_reference",
            "material_offer_name",
            "material_variation_uid",
            "material_variation_const",
            "material_variation_value",
            "material_variation_reference",
            "material_variation_name",
            "material_modification_uid",
            "material_modification_const",
            "material_modification_value",
            "material_modification_reference",
            "material_modification_name",
            "material_article",
            "material_image",
            "material_image_ext",
            "material_image_cdn",
            "category_name",
            "category_url",
            "material_quantity",
            "material_price",
            "material_currency",
            "category_section_field",
        ];


        foreach($current as $key => $value)
        {
            self::assertTrue(in_array($key, $array_keys), sprintf('Появился новый ключ %s', $key));
        }

        foreach($array_keys as $key)
        {
            self::assertTrue(array_key_exists($key, $current), sprintf('Неизвестный новый ключ %s', $key));
        }


        /**
         * category_section_field
         */


        self::assertTrue(json_validate($current['category_section_field']));
        $current = json_decode($current['category_section_field'], true);
        $current = current($current);

        $array_keys = [
            '0',
            "field_uid",
            "field_card",
            "field_name",
            "field_type",
            "field_const",
            "field_trans",
            "field_value",
            "field_public",
            "field_alternative",
        ];

        foreach($current as $key => $value)
        {
            self::assertTrue(in_array($key, $array_keys), sprintf('Появился новый ключ %s', $key));
        }

        foreach($array_keys as $key)
        {
            self::assertTrue(array_key_exists($key, $current), sprintf('Неизвестный новый ключ %s', $key));
        }
    }
}