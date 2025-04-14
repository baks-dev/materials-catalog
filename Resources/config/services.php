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
use BaksDev\Materials\Catalog\Repository\MaterialsChoice\MaterialsChoiceRepository;
use BaksDev\Materials\Catalog\Routing\BaksRoutingLoader;
use BaksDev\Products\Product\Repository\MaterialsChoice\MaterialsChoiceInterface;

return static function(ContainerConfigurator $container) {

    $services = $container->services()
        ->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services->load(BaksDevMaterialsCatalogBundle::NAMESPACE, BaksDevMaterialsCatalogBundle::PATH)
        ->exclude([
            BaksDevMaterialsCatalogBundle::PATH.'{Entity,Resources,Type}',
            BaksDevMaterialsCatalogBundle::PATH.'**'.DIRECTORY_SEPARATOR.'*Result.php',
            BaksDevMaterialsCatalogBundle::PATH.'**'.DIRECTORY_SEPARATOR.'*Message.php',
            BaksDevMaterialsCatalogBundle::PATH.'**'.DIRECTORY_SEPARATOR.'*DTO.php',
        ]);

    $services->set(MaterialsChoiceInterface::class)->class(MaterialsChoiceRepository::class);
};
