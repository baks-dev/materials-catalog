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

namespace BaksDev\Materials\Catalog\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Materials\Catalog\Repository\MaterialDetail\MaterialDetailByUidInterface;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use chillerlan\QRCode\QRCode;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_MATERIAL')]
final class QrcodeController extends AbstractController
{
    #[Route('/admin/material/qrcode/{material}', name: 'admin.qrcode', methods: ['GET', 'POST'])]
    public function qrcode(
        MaterialDetailByUidInterface $materialInfo,
        #[ParamConverter(MaterialEventUid::class)] MaterialEventUid $material,
        #[ParamConverter(MaterialOfferUid::class)] $offer = null,
        #[ParamConverter(MaterialVariationUid::class)] $variation = null,
        #[ParamConverter(MaterialModificationUid::class)] $modification = null,
    ): Response
    {

        $info = $materialInfo
            ->event($material)
            ->offer($offer)
            ->variation($variation)
            ->modification($modification)
            ->find();

        if(!$info)
        {
            throw new InvalidArgumentException('Сырьё не найден');
        }

        $data = null;

        if($modification)
        {
            $data = sprintf('%s', $modification);
        }

        if($data === null && $variation)
        {
            $data = sprintf('%s', $variation);
        }

        if($data === null && $offer)
        {
            $data = sprintf('%s', $offer);
        }

        /** Идентификатор События!!! сырья */
        if($data === null && $material)
        {
            $data = sprintf('%s', $material);
        }


        return $this->render(
            [
                'qrcode' => (new QRCode())->render($data),
                'item' => $info
            ]
        );
    }
}
