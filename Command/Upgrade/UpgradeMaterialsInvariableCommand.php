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

namespace BaksDev\Materials\Catalog\Command\Upgrade;

use BaksDev\Materials\Catalog\Entity\MaterialInvariable;
use BaksDev\Materials\Catalog\Repository\AllMaterialsIdentifier\AllMaterialsIdentifierInterface;
use BaksDev\Materials\Catalog\Repository\MaterialInvariable\MaterialInvariableInterface;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Catalog\UseCase\Admin\Invariable\MaterialInvariableDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\Invariable\MaterialInvariableHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'baks:upgrade:material:invariable',
    description: 'Обновляет Invariable всего сырья'
)]
class UpgradeMaterialsInvariableCommand extends Command
{
    public function __construct(
        private readonly AllMaterialsIdentifierInterface $allMaterialsIdentifier,
        private readonly MaterialInvariableInterface $materialInvariable,
        private readonly MaterialInvariableHandler $materialInvariableHandler,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('argument', InputArgument::OPTIONAL, 'Описание аргумента');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('Processing...');

        $materials = $this->allMaterialsIdentifier->findAll();

        /** @var array{
         * "material_id",
         * "material_event" ,
         * "offer_id" ,
         * "offer_const",
         * "variation_id" ,
         * "variation_const" ,
         * "modification_id",
         * "modification_const"
         * } $material
         */

        foreach($materials as $material)
        {
            $progressIndicator->advance();

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
                    $io->error(sprintf('%s: Ошибка при обновлении MaterialInvariable', $handle));
                }
            }
        }

        $progressIndicator->finish('Finished');

        $io->success('Обновление успешно завершено');

        return Command::SUCCESS;
    }
}