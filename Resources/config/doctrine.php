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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Materials\Catalog\BaksDevMaterialsCatalogBundle;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcode;
use BaksDev\Materials\Catalog\Type\Barcode\MaterialBarcodeType;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventType;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\File\MaterialFileType;
use BaksDev\Materials\Catalog\Type\File\MaterialFileUid;
use BaksDev\Materials\Catalog\Type\Invariable\MaterialInvariableType;
use BaksDev\Materials\Catalog\Type\Invariable\MaterialInvariableUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConstType;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferType;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Image\MaterialOfferImageType;
use BaksDev\Materials\Catalog\Type\Offers\Image\MaterialOfferImageUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConstType;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationType;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Image\MaterialOfferVariationImageType;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Image\MaterialOfferVariationImageUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConstType;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationType;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Image\MaterialOfferVariationModificationImageType;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Image\MaterialOfferVariationModificationImageUid;
use BaksDev\Materials\Catalog\Type\Photo\MaterialPhotoType;
use BaksDev\Materials\Catalog\Type\Photo\MaterialPhotoUid;
use BaksDev\Materials\Catalog\Type\Settings\MaterialSettingsIdentifier;
use BaksDev\Materials\Catalog\Type\Settings\MaterialSettingsType;
use BaksDev\Materials\Catalog\Type\Video\MaterialVideoType;
use BaksDev\Materials\Catalog\Type\Video\MaterialVideoUid;
use Symfony\Config\DoctrineConfig;

return static function(ContainerConfigurator $container, DoctrineConfig $doctrine) {

    /* MaterialUid */

    $doctrine->dbal()->type(MaterialEventUid::TYPE)->class(MaterialEventType::class);
    $doctrine->dbal()->type(MaterialOfferConst::TYPE)->class(MaterialOfferConstType::class);
    $doctrine->dbal()->type(MaterialFileUid::TYPE)->class(MaterialFileType::class);
    $doctrine->dbal()->type(MaterialOfferUid::TYPE)->class(MaterialOfferType::class);
    $doctrine->dbal()->type(MaterialSettingsIdentifier::TYPE)->class(MaterialSettingsType::class);
    $doctrine->dbal()->type(MaterialPhotoUid::TYPE)->class(MaterialPhotoType::class);
    $doctrine->dbal()->type(MaterialVideoUid::TYPE)->class(MaterialVideoType::class);
    $doctrine->dbal()->type(MaterialOfferImageUid::TYPE)->class(MaterialOfferImageType::class);

    $doctrine->dbal()->type(MaterialOfferVariationImageUid::TYPE)->class(MaterialOfferVariationImageType::class);
    $doctrine->dbal()->type(MaterialVariationConst::TYPE)->class(MaterialVariationConstType::class);
    $doctrine->dbal()->type(MaterialVariationUid::TYPE)->class(MaterialVariationType::class);

    $doctrine->dbal()->type(MaterialOfferVariationModificationImageUid::TYPE)->class(MaterialOfferVariationModificationImageType::class);
    $doctrine->dbal()->type(MaterialModificationConst::TYPE)->class(MaterialModificationConstType::class);
    $doctrine->dbal()->type(MaterialModificationUid::TYPE)->class(MaterialModificationType::class);
    $doctrine->dbal()->type(MaterialBarcode::TYPE)->class(MaterialBarcodeType::class);
    $doctrine->dbal()->type(MaterialInvariableUid::TYPE)->class(MaterialInvariableType::class);

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    /** Value Resolver */

    $services->set(MaterialUid::class)->class(MaterialUid::class);
    $services->set(MaterialEventUid::class)->class(MaterialEventUid::class);

    $services->set(MaterialOfferUid::class)->class(MaterialOfferUid::class);
    $services->set(MaterialOfferConst::class)->class(MaterialOfferConst::class);

    $services->set(MaterialVariationUid::class)->class(MaterialVariationUid::class);
    $services->set(MaterialVariationConst::class)->class(MaterialVariationConst::class);

    $services->set(MaterialModificationUid::class)->class(MaterialModificationUid::class);
    $services->set(MaterialModificationConst::class)->class(MaterialModificationConst::class);

    $emDefault = $doctrine->orm()->entityManager('default')->autoMapping(true);

    $emDefault
        ->mapping('materials-catalog')
        ->type('attribute')
        ->dir(BaksDevMaterialsCatalogBundle::PATH.'Entity')
        ->isBundle(false)
        ->prefix(BaksDevMaterialsCatalogBundle::NAMESPACE.'\\Entity')
        ->alias('materials-catalog');
};
