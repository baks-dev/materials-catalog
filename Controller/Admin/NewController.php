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
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\MaterialCategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialForm;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\MaterialHandler;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL_NEW')]
final class NewController extends AbstractController
{
    #[Route('/admin/material/new/{id}', name: 'admin.newedit.new', defaults: ['id' => null], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        MaterialHandler $materialHandler,
        ?MaterialEventUid $id = null,
    ): Response
    {

        $MaterialDTO = new MaterialDTO();
        $this->isAdmin() ?: $MaterialDTO->getInfo()->setProfile($this->getProfileUid());


        // Если передан идентификатор события - копируем
        if($id)
        {
            $Event = $entityManager->getRepository(MaterialEvent::class)->find($id);

            if($Event)
            {
                $Event->getDto($MaterialDTO);
                $MaterialDTO->setId(new MaterialEventUid());
            }
        }

        // Если передана категория - присваиваем для настроек (свойства, ТП)
        if($request->get('category'))
        {
            $CategoryCollectionDTO = new MaterialCategoryCollectionDTO();
            $CategoryCollectionDTO->rootCategory();
            $CategoryCollectionDTO->setCategory(new CategoryMaterialUid($request->get('category')));
            $MaterialDTO->addCategory($CategoryCollectionDTO);
        }

        // Форма добавления
        $form = $this->createForm(MaterialForm::class, $MaterialDTO);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('material'))
        {
            $this->refreshTokenForm($form);

            $handle = $materialHandler->handle($MaterialDTO);

            $this->addFlash(
                'page.new',
                $handle instanceof Material ? 'success.new' : 'danger.new',
                'materials-catalog.admin',
                $handle
            );

            return $this->redirectToRoute('materials-catalog:admin.index');
        }

        return $this->render(['form' => $form->createView()]);
    }
}
