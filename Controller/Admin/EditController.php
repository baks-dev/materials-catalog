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

namespace BaksDev\Materials\Catalog\Controller\Admin;

use App\Kernel;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\CategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialForm;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialHandler;
use BaksDev\Materials\Category\Type\Id\CategoryProductUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_EDIT')]
final class EditController extends AbstractController
{
    #[Route('/admin/material/edit/{id}', name: 'admin.newedit.edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity] MaterialEvent $Event,
        Request $request,
        MaterialHandler $productHandler
    ): Response
    {

        $ProductDTO = new MaterialDTO();
        $ProductDTO = $Event->getDto($ProductDTO);

        // Если передана категория - присваиваем для подгрузки настроект (свойства, ТП)
        if($request->get('category'))
        {
            /** @var CategoryCollectionDTO $category */
            foreach($ProductDTO->getCategory() as $category)
            {
                if($category->getRoot())
                {
                    if($request->get('category') === 'null')
                    {
                        $category->setCategory(null);
                        break;
                    }

                    $category->setCategory(new CategoryProductUid($request->get('category')));
                }
            }

            if($category->getCategory() === null && $request->get('category') !== 'null')
            {
                $category->setRoot(true);
                $category->setCategory(new CategoryProductUid($request->get('category')));
            }
        }

        // Форма добавления
        $form = $this->createForm(MaterialForm::class, $ProductDTO);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('product'))
        {
            $this->refreshTokenForm($form);

            $handle = $productHandler->handle($ProductDTO);

            $this->addFlash(
                'admin.page.edit',
                $handle instanceof Material ? 'admin.success.edit' : 'admin.danger.edit',
                'materials-catalog.admin',
                $handle
            );

            return $this->redirectToRoute('materials-catalog:admin.index');
        }

        return $this->render(['form' => $form->createView()]);
    }
}
