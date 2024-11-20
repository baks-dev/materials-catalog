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

namespace BaksDev\Materials\Catalog\Controller\User;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Materials\Catalog\Entity\Info\ProductInfo;
use BaksDev\Materials\Catalog\Repository\ProductAlternative\ProductAlternativeInterface;
use BaksDev\Materials\Catalog\Repository\ProductDetail\ProductDetailByValueInterface;
use BaksDev\Materials\Catalog\Repository\ProductDetailOffer\ProductDetailOfferInterface;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductForm;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class DetailController extends AbstractController
{
    #[Route('/catalog/{category}/{url}/{offer}/{variation}/{modification}/{postfix}', name: 'user.detail')]
    public function index(
        Request $request,
        #[MapEntity(mapping: ['url' => 'url'])] ProductInfo $info,
        ProductDetailByValueInterface $productDetail,
        ProductDetailOfferInterface $productDetailOffer,
        ProductAlternativeInterface $productAlternative,
        string $offer,
        ?string $variation = null,
        ?string $modification = null,
        ?string $postfix = null,
    ): Response
    {

        $productCard = $productDetail->fetchProductAssociative(
            $info->getProduct(),
            $offer,
            $variation,
            $modification,
            $postfix
        );

        /** Другие ТП данного продукта */
        $productOffer = $productDetailOffer->fetchProductOfferAssociative($info->getProduct());

        /** Статус, если товара с ТП, вариантом или модификацией не существует */
        $status = 200;

        $NOW = new DateTimeImmutable();

        if(
            !$productCard ||
            ($productCard['product_offer_value'] === null) ||
            ($variation !== null && $productCard['product_variation_value'] === null) ||
            ($modification !== null && $productCard['product_modification_value'] === null) ||
            $productCard['active'] === false ||
            (!empty($productCard['active_from']) && new DateTimeImmutable($productCard['active_from']) > $NOW) ||
            (!empty($productCard['active_to']) && new DateTimeImmutable($productCard['active_to']) < $NOW)

        )
        {
            $status = 404;
        }


        if($status === 404)
        {
            return $this->render(
                [
                    'card' => $productCard,
                    'offers' => $productOffer,

                    'offer' => $offer,
                    'variation' => $variation,
                    'modification' => $modification,
                ],
                //fileName: 'notfound/template.html.twig',
                routingName: 'user.detail.notfound',
                response: new Response(status: $status)
            );
        }

        /* Удаляем сессию фильтра каталога */
        $request->getSession()->set('catalog_filter', null);

        /** Список альтернатив  */
        $alternativeProperty = json_decode($productCard['category_section_field'], false, 512, JSON_THROW_ON_ERROR);

        /* получаем свойства, учавствующие в фильтре альтернатив */
        $alternativeField = array_filter($alternativeProperty, function($v) {
            return $v->field_alternative === true;
        }, ARRAY_FILTER_USE_BOTH);


        $alternative = null;
        if(!empty($productCard['product_offer_value']))
        {
            $alternative = $productAlternative->fetchAllAlternativeAssociative(
                $productCard['product_offer_value'],
                $productCard['product_variation_value'],
                $productCard['product_modification_value'],
                $alternativeField
            );
        }

        /* Корзина */

        $AddProductBasketDTO = new OrderProductDTO();
        $form = $this->createForm(OrderProductForm::class, $AddProductBasketDTO, [
            'action' => $this->generateUrl(
                'orders-order:user.add',
                [
                    'product' => $productCard['event'],
                    'offer' => $productCard['product_offer_uid'],
                    'variation' => $productCard['product_variation_uid'],
                    'modification' => $productCard['product_modification_uid'],
                ]
            ),
        ]);

        // Поиск по всему сайту
        $allSearch = new SearchDTO($request);
        $allSearchForm = $this->createForm(SearchForm::class, $allSearch, [
            'action' => $this->generateUrl('core:search'),
        ]);

        return $this->render([
            'card' => $productCard,
            'offers' => $productOffer,
            'alternative' => $alternative,
            'offer' => $offer,
            'variation' => $variation,
            'modification' => $modification,
            'basket' => $form->createView(),
            'all_search' => $allSearchForm->createView(),
        ]);
    }
}
