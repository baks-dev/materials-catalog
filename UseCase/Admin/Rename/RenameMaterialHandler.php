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

namespace BaksDev\Materials\Catalog\UseCase\Admin\Rename;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Materials\Catalog\Entity;
use BaksDev\Materials\Catalog\Messenger\MaterialMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class RenameMaterialHandler
{
    public function __construct(
        #[Target('materialsCatalogLogger')] private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private MessageDispatchInterface $messageDispatch,
    ) {}


    public function handle(RenameMaterialDTO $command): Entity\Material|string
    {
        /**
         *  Валидация WbBarcodeDTO
         */
        $errors = $this->validator->validate($command);

        if(count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [self::class.':'.__LINE__]);

            return $uniqid;
        }

        if(!$command->getEvent())
        {
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: Не указан идентификатор события', $uniqid), [self::class.':'.__LINE__]);

            return $uniqid;
        }


        $EventRepo = $this->entityManager->getRepository(Entity\Event\MaterialEvent::class)->find(
            $command->getEvent(),
        );

        if(null === $EventRepo)
        {
            $uniqid = uniqid('', false);

            $this->logger->error(sprintf(
                '%s: Событие MaterialEvent не найдено (event: %s)',
                $uniqid,
                $command->getEvent()
            ), [self::class.':'.__LINE__]);

            return $uniqid;
        }

        $EventRepo->setEntity($command);
        $EventRepo->setEntityManager($this->entityManager);
        $Event = $EventRepo->cloneEntity();
        //        $this->entityManager->clear();
        //        $this->entityManager->persist($Event);

        // Получаем сырьё
        $Material = $this->entityManager->getRepository(Entity\Material::class)
            ->findOneBy(['event' => $command->getEvent()]);


        if(empty($Material))
        {
            $uniqid = uniqid('', false);

            $this->logger->error(sprintf(
                '%s: Агрегат Material не найден, либо был изменен (event: %s)',
                $uniqid,
                $command->getEvent()
            ), [self::class.':'.__LINE__]);

            return $uniqid;
        }

        $Material->setEvent($Event); // Обновляем событие агрегата


        // Валидация события
        $errors = $this->validator->validate($Event);

        if(count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [self::class.':'.__LINE__]);

            return $uniqid;
        }


        // Валидация агрегата
        $errors = $this->validator->validate($Material);

        if(count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [self::class.':'.__LINE__]);

            return $uniqid;
        }


        $this->entityManager->flush();

        /* Отправляем событие в шину  */
        $this->messageDispatch->dispatch(
            message: new MaterialMessage($Material->getId(), $Material->getEvent(), $command->getEvent()),
            transport: 'materials-catalog',
        );

        return $Material;
    }
}
