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

namespace BaksDev\Materials\Catalog\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterForm;
use BaksDev\Materials\Catalog\Repository\AllMaterials\AllMaterialsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity(['ROLE_MATERIAL', 'ROLE_MATERIAL_INDEX'])]
final class IndexController extends AbstractController
{
    #[Route('/admin/materials/{page<\d+>}', name: 'admin.index', methods: ['GET', 'POST',])]
    public function index(
        Request $request,
        AllMaterialsInterface $getAllMaterial,
        int $page = 0,
    ): Response
    {
        /**
         * Фильтр сырья по ТП
         */
        $filter = new MaterialFilterDTO($request);
        $filterForm = $this->createForm(MaterialFilterForm::class, $filter, [
            'action' => $this->generateUrl('materials-catalog:admin.index'),
        ]);

        $filterForm->handleRequest($request);

        //!$filterForm->isSubmitted() ?: $this->redirectToReferer();


        // Поиск
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('materials-catalog:admin.index'),]
            )
            ->handleRequest($request);


        $isFilter = (bool) ($search->getQuery() || $filter->getOffer() || $filter->getVariation() || $filter->getModification());


        $getAllMaterial
            ->search($search)
            ->filter($filter);

        if($isFilter)
        {
            // Получаем список торговых предложений
            $query = $getAllMaterial->getAllMaterialsOffers($this->getProfileUid());
        }
        else
        {
            // Получаем список сырья
            $query = $getAllMaterial->getAllMaterials($this->getProfileUid());
        }


        return $this->render(
            [
                'query' => $query,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
                //'profile' => $profileForm->createView(),
            ],
            file: $isFilter ? 'offers.html.twig' : 'material.html.twig'
        );
    }
}
