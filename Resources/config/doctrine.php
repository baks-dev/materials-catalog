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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Materials\Catalog\BaksDevMaterialsCatalogBundle;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventType;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialType;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use Symfony\Config\DoctrineConfig;

return static function(ContainerConfigurator $container, DoctrineConfig $doctrine) {

    /* ProductUid */
    $doctrine->dbal()->type(MaterialUid::TYPE)->class(MaterialType::class);
    $doctrine->dbal()->type(MaterialEventUid::TYPE)->class(MaterialEventType::class);

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    /** Value Resolver */

    $services->set(MaterialUid::class)->class(MaterialUid::class);
    $services->set(MaterialEventUid::class)->class(MaterialEventUid::class);

    $emDefault = $doctrine->orm()->entityManager('default')->autoMapping(true);

    $emDefault->mapping('materials-catalog')
        ->type('attribute')
        ->dir(BaksDevMaterialsCatalogBundle::PATH.'Entity')
        ->isBundle(false)
        ->prefix(BaksDevMaterialsCatalogBundle::NAMESPACE.'\\Entity')
        ->alias('materials-catalog');
};
