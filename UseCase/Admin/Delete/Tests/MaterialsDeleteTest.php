<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Catalog\UseCase\Admin\Delete\Tests;

use BaksDev\Materials\Catalog\Controller\Admin\Tests\DeleteAdminControllerTest;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Repository\CurrentMaterialEvent\CurrentMaterialEventInterface;
use BaksDev\Materials\Catalog\UseCase\Admin\Delete\MaterialDeleteDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\Delete\MaterialDeleteHandler;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Tests\MaterialsCatalogEditTest;
use BaksDev\Materials\Category\UseCase\Admin\NewEdit\Tests\CategoryMaterialNewTest;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[When(env: 'test')]
#[Group('materials-catalog')]
class MaterialsDeleteTest extends KernelTestCase
{
    public static function tearDownAfterClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(Material::class)
            ->findOneBy(['id' => MaterialUid::TEST]);

        if($main)
        {
            $em->remove($main);
        }


        $event = $em->getRepository(MaterialEvent::class)
            ->findBy(['main' => MaterialUid::TEST]);

        foreach($event as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
        $em->clear();

        /** Удаляем тестовую категорию */
        CategoryMaterialNewTest::setUpBeforeClass();
    }

    #[DependsOnClass(MaterialsCatalogEditTest::class)]
    #[DependsOnClass(DeleteAdminControllerTest::class)]
    public function testUseCase(): void
    {
        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var CurrentMaterialEventInterface $MaterialCurrentEvent */
        $MaterialCurrentEvent = self::getContainer()->get(CurrentMaterialEventInterface::class);
        $MaterialEvent = $MaterialCurrentEvent->findByMaterial(MaterialUid::TEST);

        self::assertNotNull($MaterialEvent);

        /** @see MaterialDeleteDTO */
        $MaterialDeleteDTO = new MaterialDeleteDTO();
        $MaterialEvent->getDto($MaterialDeleteDTO);


        /** @var MaterialDeleteHandler $MaterialDeleteHandler */
        $MaterialDeleteHandler = self::getContainer()->get(MaterialDeleteHandler::class);
        $handle = $MaterialDeleteHandler->handle($MaterialDeleteDTO);

        self::assertTrue($handle instanceof Material);

    }
}
