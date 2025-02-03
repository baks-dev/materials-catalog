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

namespace BaksDev\Materials\Catalog\Messenger\Invariable;


use BaksDev\Materials\Catalog\Entity\MaterialInvariable;
use BaksDev\Materials\Catalog\Messenger\MaterialMessage;
use BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier\AllMaterialsIdentifierInterface;
use BaksDev\Materials\Catalog\Repository\MaterialInvariable\MaterialInvariableInterface;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\UseCase\Admin\Invariable\MaterialInvariableDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\Invariable\MaterialInvariableHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class UpdateMaterialInvariableHandler
{
    public function __construct(
        #[Target('materialsCatalogLogger')] private LoggerInterface $logger,
        private AllMaterialsIdentifierInterface $allMaterialsIdentifier,
        private MaterialInvariableInterface $materialInvariable,
        private MaterialInvariableHandler $materialInvariableHandler,
    ) {}

    /**
     * Метод обновляет сущность Invariable при изменении сырья
     */
    public function __invoke(MaterialMessage $message): void
    {
        $materials = $this->allMaterialsIdentifier
            ->forMaterial($message->getId())
            ->findAll();

        if(false === $materials)
        {
            return;
        }

        foreach($materials as $material)
        {
            $MaterialInvariableUid = $this->materialInvariable
                ->material($material['material_id'])
                ->offer($material['offer_const'])
                ->variation($material['variation_const'])
                ->modification($material['modification_const'])
                ->find();

            if(false === $MaterialInvariableUid)
            {
                $MaterialInvariableDTO = new MaterialInvariableDTO();

                $MaterialInvariableDTO
                    ->setMaterial(new MaterialUid($material['material_id']))
                    ->setOffer($material['offer_const'] ? new MaterialOfferConst($material['offer_const']) : null)
                    ->setVariation($material['variation_const'] ? new MaterialVariationConst($material['variation_const']) : null)
                    ->setModification($material['modification_const'] ? new MaterialModificationConst($material['modification_const']) : null);

                $handle = $this->materialInvariableHandler->handle($MaterialInvariableDTO);

                if(false === ($handle instanceof MaterialInvariable))
                {
                    $this->logger->critical(sprintf('%s: Ошибка при обновлении MaterialInvariable', $handle));
                }
            }
        }
    }
}
