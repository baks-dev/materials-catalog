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

namespace BaksDev\Materials\Catalog\Repository\AllExistsMaterials;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Description\MaterialDescription;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\Image\MaterialOfferImage;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Image\MaterialVariationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\MaterialVariationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Photo\MaterialPhoto;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;

final class AllExistsMaterialsRepository implements AllExistsMaterialsInterface
{
    private ?SearchDTO $search = null;

    private ?MaterialFilterDTO $filter = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(MaterialFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }


    /** Получает всю сырьё, имеющуюся в наличии */
    public function getAllMaterials(): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $dbal
            ->select('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material');

        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material.event AND material_trans.local = :local'
            );


        $dbal
            ->addSelect('material_desc.preview AS material_preview')
            ->addSelect('material_desc.description AS material_description')
            ->leftJoin(
                'material',
                MaterialDescription::class,
                'material_desc',
                'material_desc.event = material.event AND material_desc.device = :device '
            )->setParameter('device', 'pc');


        /* MaterialInfo */
        $dbal->leftJoin(
            'material',
            MaterialInfo::class,
            'material_info',
            'material_info.material = material.id'
        );


        /** Ответственное лицо (Профиль пользователя) */

        $dbal->leftJoin(
            'material_info',
            UserProfile::class,
            'users_profile',
            'users_profile.id = material_info.profile'
        );

        $dbal
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->leftJoin(
                'users_profile',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile.event'
            );


        /* Базовая Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );


        /** Торговое предложение */

        $dbal
            ->addSelect('material_offer.id as material_offer_id')
            ->addSelect('material_offer.value as material_offer_value')
            ->leftJoin(
                'material',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material.event'
            );

        if($this->filter->getOffer())
        {
            $dbal->andWhere('material_offer.value = :offer');
            $dbal->setParameter('offer', $this->filter->getOffer());
        }


        /* Тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference as material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );


        /* Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );


        /** Множественные варианты торгового предложения */

        $dbal
            ->addSelect('material_variation.id as material_variation_id')
            ->addSelect('material_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id'
            );

        /* Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'material_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );


        if($this->filter->getVariation())
        {
            $dbal->andWhere('material_variation.value = :variation');
            $dbal->setParameter('variation', $this->filter->getVariation());
        }


        /* Цена множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        );


        /* Тип множественного варианта торгового предложения */
        $dbal
            ->addSelect('category_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_variation',
                'category_variation.id = material_variation.category_variation'
            );


        /** Модификация множественного варианта */
        $dbal
            ->addSelect('material_modification.id as material_modification_id')
            ->addSelect('material_modification.value as material_modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id '
            );

        /* Наличие и резерв модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_modification.id'
        );


        if($this->filter->getModification())
        {
            $dbal->andWhere('material_modification.value = :modification');
            $dbal->setParameter('modification', $this->filter->getModification());
        }

        /** Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_modification',
                'category_modification.id = material_modification.category_modification'
            );


        /** Артикул сырья */

        $dbal->addSelect('
            COALESCE(
                material_modification.article, 
                material_variation.article, 
                material_offer.article, 
                material_info.article
            ) AS material_article
		');


        /** Фото сырья */

        $dbal->leftJoin(
            'material',
            MaterialPhoto::class,
            'material_photo',
            'material_photo.event = material.event AND material_photo.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            'material_variation_image.variation = material_variation.id 
            AND material_variation_image.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            'material_offer_images.offer = material_offer.id 
            AND material_offer_images.root = true'
        );

        $dbal->addSelect(
            "
			CASE
			   WHEN material_variation_image.name IS NOT NULL
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialVariationImage::class)."' , '/', material_variation_image.name)
			   
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialOfferImage::class)."' , '/', material_offer_images.name)
			   
			   WHEN material_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialPhoto::class)."' , '/', material_photo.name)
			   
			   ELSE NULL
			END AS material_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.ext
			   
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.ext
			   
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.ext
			   
			   ELSE NULL
			END AS material_image_ext
		");


        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.cdn
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.cdn
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.cdn
					
			   ELSE NULL
			END AS material_image_cdn
		");


        /* Категория */
        $dbal->join(
            'material',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material.event AND material_event_category.root = true'
        );

        if($this->filter->getCategory())
        {
            $dbal->andWhere('material_event_category.category = :category');
            $dbal->setParameter('category', $this->filter->getCategory(), CategoryMaterialUid::TYPE);
        }

        $dbal->join(
            'material_event_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_event_category.category'
        );

        $dbal->addSelect('category_trans.name AS category_name');

        $dbal->leftJoin(
            'category',
            CategoryMaterialTrans::class,
            'category_trans',
            'category_trans.event = category.event AND category_trans.local = :local'
        );


        $UANTITY = '
			CASE
			
			   WHEN material_modification_quantity.quantity > 0 AND material_modification_quantity.quantity > material_modification_quantity.reserve 
			   THEN (material_modification_quantity.quantity - material_modification_quantity.reserve)

			   WHEN material_variation_quantity.quantity > 0 AND material_variation_quantity.quantity > material_variation_quantity.reserve  
			   THEN (material_variation_quantity.quantity - material_variation_quantity.reserve)
			
			   WHEN material_offer_quantity.quantity > 0 AND material_offer_quantity.quantity > material_offer_quantity.reserve 
			   THEN (material_offer_quantity.quantity - material_offer_quantity.reserve)

			   WHEN material_price.quantity > 0 AND material_price.quantity > material_price.reserve 
			   THEN (material_price.quantity - material_price.reserve)

			   ELSE 0
			   
			END';

        $dbal->addSelect($UANTITY.' AS material_quantity ');
        $dbal->andWhere($UANTITY.' > 0');


        if($this->search->getQuery())
        {

            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('material.id')
                ->addSearchEqualUid('material.event')
                ->addSearchEqualUid('material_variation.id')
                ->addSearchEqualUid('material_modification.id')
                ->addSearchLike('material_trans.name')
                //->addSearchLike('material_trans.preview')
                ->addSearchLike('material_info.article')
                ->addSearchLike('material_offer.article')
                ->addSearchLike('material_modification.article')
                ->addSearchLike('material_modification.article')
                ->addSearchLike('material_variation.article');

        }

        $dbal->orderBy('material.event', 'DESC');

        return $this->paginator->fetchAllAssociative($dbal);

    }


}
