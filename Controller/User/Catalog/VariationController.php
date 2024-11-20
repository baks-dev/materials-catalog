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

namespace BaksDev\Materials\Catalog\Controller\User\Catalog;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Materials\Catalog\Forms\ProductCategoryFilter\User\ProductCategoryFilterDTO;
use BaksDev\Materials\Catalog\Forms\ProductCategoryFilter\User\ProductCategoryFilterForm;
use BaksDev\Materials\Catalog\Repository\AllProductsByCategory\AllProductsByCategoryInterface;
use BaksDev\Products\Category\Repository\CategoryByUrl\CategoryByUrlInterface;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
final class VariationController extends AbstractController
{
    #[Route('/catalog/{category}/variation/{offer}/{variation}/{page<\d+>}', name: 'user.catalog.variation')]
    public function index(
        Request $request,
        AllProductsByCategoryInterface $productsByCategory,
        CategoryByUrlInterface $categoryByUrl,
        string $category,
        string $offer = 'all',
        string $variation = 'all',
        int $page = 0,
    ): Response
    {
        $info = $categoryByUrl->findByUrl($category);

        if(!$info)
        {
            throw new RouteNotFoundException('Page Not Found');
        }

        $CategoryUid = new CategoryProductUid($info['category_id']);

        $ProductCategoryFilterDTO = new ProductCategoryFilterDTO($CategoryUid);
        $ProductCategoryFilterDTO->setOffer($offer !== 'all' ? $offer : null);
        $ProductCategoryFilterDTO->setVariation($variation !== 'all' ? $variation : null);

        $filterForm = $this->createForm(
            ProductCategoryFilterForm::class,
            $ProductCategoryFilterDTO,
            ['action' => $this->generateUrl('materials-catalog:user.catalog.category', ['category' => $category])]
        );
        $filterForm->handleRequest($request);

        $property = null;
        $fields = null;
        foreach($filterForm->all() as $item)
        {
            if($item instanceof Form && !empty($item->getViewData()))
            {

                if($item->getConfig()->getMapped())
                {
                    continue;
                }

                $property[$item->getName()] = $item->getNormData();

                $fields[] = [
                    'field_name' => $item->getConfig()->getOption('label'),
                    'field_value' => $item->getNormData(),
                    'field_type' => $item->getConfig()->getOption('block_name'),
                ];
            }
        }


        $otherProducts = false;
        $Products = $productsByCategory
            ->filter($ProductCategoryFilterDTO)
            ->property($property)
            ->fetchAllProductByCategoryAssociative($CategoryUid, 'AND');

        /** Если список пуст - пробуем предложить другие варианты */
        if(!$Products->getData())
        {
            $Products = $productsByCategory
                ->filter($ProductCategoryFilterDTO)
                ->property($property)
                ->fetchAllProductByCategoryAssociative($CategoryUid, 'OR');

            if($Products->getData())
            {
                $otherProducts = true;
            }
        }


        // Поиск по всему сайту
        $allSearch = new SearchDTO($request);
        $allSearchForm = $this->createForm(SearchForm::class, $allSearch, [
            'action' => $this->generateUrl('core:search'),
        ]);


        return $this->render([
            'category' => $info,
            'products' => $Products,
            'filter' => $filterForm->createView(),
            'other' => $otherProducts,
            'fields' => $fields,
            'all_search' => $allSearchForm->createView(),
        ], routingName: 'user.catalog.category');
    }

}
