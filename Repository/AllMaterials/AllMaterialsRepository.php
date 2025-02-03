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

namespace BaksDev\Materials\Catalog\Repository\AllMaterials;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Elastic\Api\Index\ElasticGetIndex;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Description\MaterialDescription;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
use BaksDev\Materials\Catalog\Entity\Material;
use BaksDev\Materials\Catalog\Entity\Offers\Image\MaterialOfferImage;
use BaksDev\Materials\Catalog\Entity\Offers\MaterialOffer;
use BaksDev\Materials\Catalog\Entity\Offers\Price\MaterialOfferPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Quantity\MaterialOfferQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Image\MaterialVariationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\MaterialVariation;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Image\MaterialModificationImage;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\MaterialModification;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Price\MaterialModificationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Modification\Quantity\MaterialModificationQuantity;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Price\MaterialVariationPrice;
use BaksDev\Materials\Catalog\Entity\Offers\Variation\Quantity\MaterialsVariationQuantity;
use BaksDev\Materials\Catalog\Entity\Photo\MaterialPhoto;
use BaksDev\Materials\Catalog\Entity\Price\MaterialPrice;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin\MaterialFilterDTO;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Info\CategoryMaterialInfo;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Override;

//use BaksDev\Materials\Category\Entity as CategoryEntity;

final class AllMaterialsRepository implements AllMaterialsInterface
{
    private ?SearchDTO $search = null;
    private ?MaterialFilterDTO $filter = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
        private ?ElasticGetIndex $elasticGetIndex = null
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

    #[Override]
    public function getAllMaterialsOffers(UserProfileUid|string $profile): PaginatorInterface
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }


        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material');

        $dbal->leftJoin(
            'material',
            MaterialEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material_event',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material_event.id AND material_trans.local = :local'
            );


        $dbal->andWhere('material_info.profile = :profile OR material_info.profile IS NULL');
        $dbal->setParameter('profile', $profile, UserProfileUid::TYPE);


        /* MaterialInfo */

        $dbal
            ->leftJoin(
                'material_event',
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


        /** Торговое предложение */

        $dbal
            ->addSelect('material_offer.id as material_offer_id')
            ->addSelect('material_offer.const as material_offer_const')
            ->addSelect('material_offer.value as material_offer_value')
            ->leftJoin(
                'material_event',
                MaterialOffer::class,
                'material_offer',
                'material_offer.event = material_event.id'
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


        /** Множественные варианты торгового предложения */

        $dbal
            ->addSelect('material_variation.id as material_variation_id')
            ->addSelect('material_variation.const as material_variation_const')
            ->addSelect('material_variation.value as material_variation_value')
            ->leftJoin(
                'material_offer',
                MaterialVariation::class,
                'material_variation',
                'material_variation.offer = material_offer.id'
            );


        if($this->filter->getVariation())
        {
            $dbal->andWhere('material_variation.value = :variation');
            $dbal->setParameter('variation', $this->filter->getVariation());
        }


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
            ->addSelect('material_modification.const as material_modification_const')
            ->addSelect('material_modification.value as material_modification_value')
            ->leftJoin(
                'material_variation',
                MaterialModification::class,
                'material_modification',
                'material_modification.variation = material_variation.id '
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

        $dbal->addSelect("
            COALESCE(
                material_modification.article,
                material_variation.article,
                material_offer.article,
                material_info.article
            ) AS material_article
		");


        /** Фото сырья */

        $dbal->leftJoin(
            'material_event',
            MaterialPhoto::class,
            'material_photo',
            'material_photo.event = material_event.id AND material_photo.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            'material_offer_images.offer = material_offer.id AND material_offer_images.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            'material_variation_image.variation = material_variation.id AND material_variation_image.root = true'
        );

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            'material_modification_image.modification = material_modification.id AND material_modification_image.root = true'
        );

        $dbal->addSelect(
            "
			CASE
			
			    WHEN material_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialModificationImage::class)."' , '/', material_modification_image.name)
			   
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
        $dbal->leftJoin(
            'material_event',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material_event.id AND material_event_category.root = true'
        );

        if($this->filter->getCategory())
        {
            $dbal->andWhere('material_event_category.category = :category');
            $dbal->setParameter('category', $this->filter->getCategory(), CategoryMaterialUid::TYPE);
        }

        $dbal->leftJoin(
            'material_event_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_event_category.category'
        );

        $dbal
            ->addSelect('category_trans.name AS category_name')
            ->leftJoin(
                'category',
                CategoryMaterialTrans::class,
                'category_trans',
                'category_trans.event = category.event AND category_trans.local = :local'
            );


        /* Базовая Цена товара */
        $dbal->leftJoin(
            'material',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material.event'
        );

        /* Цена торгового предо жения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferPrice::class,
            'material_offer_price',
            'material_offer_price.offer = material_offer.id'
        );

        /* Цена множественного варианта */
        $dbal->leftJoin(
            'material_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        );

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        );


        /* Стоимость сырья */

        $dbal->addSelect('
			COALESCE(
                NULLIF(material_modification_price.price, 0), 
                NULLIF(material_variation_price.price, 0), 
                NULLIF(material_offer_price.price, 0), 
                NULLIF(material_price.price, 0),
                0
            ) AS material_price
		');


        /* Валюта сырья */

        $dbal->addSelect(
            '
			CASE
			   WHEN material_modification_price.price IS NOT NULL AND material_modification_price.price > 0 
			   THEN material_modification_price.currency
			   
			   WHEN material_variation_price.price IS NOT NULL AND material_variation_price.price > 0 
			   THEN material_variation_price.currency
			   
			   WHEN material_offer_price.price IS NOT NULL AND material_offer_price.price > 0 
			   THEN material_offer_price.currency
			   
			   WHEN material_price.price IS NOT NULL AND material_price.price > 0 
			   THEN material_price.currency
			   
			   ELSE NULL
			END AS material_currency
		'
        );


        /* Наличие сырья */

        /* Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        /* Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'material_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );

        $dbal
            ->leftJoin(
                'material_modification',
                MaterialModificationQuantity::class,
                'material_modification_quantity',
                'material_modification_quantity.modification = material_modification.id'
            );


        $dbal->addSelect("
			COALESCE(
                NULLIF(material_modification_quantity.quantity, 0),
                NULLIF(material_variation_quantity.quantity, 0),
                NULLIF(material_offer_quantity.quantity, 0),
                NULLIF(material_price.quantity, 0),
                0
            ) AS material_quantity
		");

        $dbal->addSelect("
			COALESCE(
                NULLIF(material_modification_quantity.reserve, 0),
                NULLIF(material_variation_quantity.reserve, 0),
                NULLIF(material_offer_quantity.reserve, 0),
                NULLIF(material_price.reserve, 0),
                0
            ) AS material_reserve
		");


        //        $dbal->addSelect(
        //            '
        //
        //
        //            CASE
        //
        //
        //			   WHEN material_modification_quantity.quantity > 0 AND material_modification_quantity.quantity > material_modification_quantity.reserve
        //			   THEN (material_modification_quantity.quantity - material_modification_quantity.reserve)
        //
        //			   WHEN material_variation_quantity.quantity > 0 AND material_variation_quantity.quantity > material_variation_quantity.reserve
        //			   THEN (material_variation_quantity.quantity - material_variation_quantity.reserve)
        //
        //			   WHEN material_offer_quantity.quantity > 0 AND material_offer_quantity.quantity > material_offer_quantity.reserve
        //			   THEN (material_offer_quantity.quantity - material_offer_quantity.reserve)
        //
        //			   WHEN material_price.quantity > 0 AND material_price.quantity > material_price.reserve
        //			   THEN (material_price.quantity - material_price.reserve)
        //
        //			   ELSE 0
        //
        //			END AS material_quantity
        //
        //		'
        //        );


        if($this->search->getQuery())
        {
            /** Поиск по модификации */
            $result = $this->elasticGetIndex ? $this->elasticGetIndex->handle(MaterialModification::class, $this->search->getQuery(), 1) : false;

            if($result)
            {
                $counter = $result['hits']['total']['value'];

                if($counter)
                {
                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $dbal
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('material_modification.id', array_column($data, "id"));

                    return $this->paginator->fetchAllAssociative($dbal);
                }
            }

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

    #[Override]
    public function getAllMaterials(UserProfileUid|string $profile): PaginatorInterface
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('material.id')
            ->addSelect('material.event')
            ->from(Material::class, 'material');

        $dbal->leftJoin(
            'material',
            MaterialEvent::class,
            'material_event',
            'material_event.id = material.event'
        );

        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material_event',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material_event.id AND material_trans.local = :local'
            );


        $dbal->andWhere('material_info.profile = :profile OR material_info.profile IS NULL');
        $dbal->setParameter('profile', $profile, UserProfileUid::TYPE);


        /* MaterialInfo */

        $dbal
            ->leftJoin(
                'material_event',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material.id'
            );

        /** Ответственное лицо (Профиль пользователя) */

        $dbal
            ->addSelect('material_info.article')
            ->leftJoin(
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


        /** Торговое предложение */

        $dbal->leftJoin(
            'material_event',
            MaterialOffer::class,
            'material_offer',
            'material_offer.event = material_event.id'
        );


        /* Тип торгового предложения */

        $dbal->leftJoin(
            'material_offer',
            CategoryMaterialOffers::class,
            'category_offer',
            'category_offer.id = material_offer.category_offer'
        );


        /** Множественные варианты торгового предложения */

        $dbal->leftJoin(
            'material_offer',
            MaterialVariation::class,
            'material_variation',
            'material_variation.offer = material_offer.id'
        );


        /* Цена множественного варианта */
        $dbal->leftJoin(
            'category_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        );


        /* Тип множественного варианта торгового предложения */
        $dbal->leftJoin(
            'material_variation',
            CategoryMaterialVariation::class,
            'category_variation',
            'category_variation.id = material_variation.category_variation'
        );


        /** Модификация множественного варианта */

        $dbal->leftJoin(
            'material_variation',
            MaterialModification::class,
            'material_modification',
            'material_modification.variation = material_variation.id '
        );

        $dbal->leftJoin(
            'material_modification',
            MaterialModificationImage::class,
            'material_modification_image',
            'material_modification_image.modification = material_modification.id AND material_modification_image.root = true'
        );


        /** Получаем тип модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            CategoryMaterialModification::class,
            'category_modification',
            'category_modification.id = material_modification.category_modification'
        );


        /** Фото сырья */

        $dbal->leftJoin(
            'material_event',
            MaterialPhoto::class,
            'material_photo',
            'material_photo.event = material_event.id AND material_photo.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialVariationImage::class,
            'material_variation_image',
            'material_variation_image.variation = material_variation.id AND material_variation_image.root = true'
        );

        $dbal->leftJoin(
            'material_offer',
            MaterialOfferImage::class,
            'material_offer_images',
            'material_offer_images.offer = material_offer.id AND material_offer_images.root = true'
        );

        $dbal->addSelect(
            "
			CASE
			    WHEN material_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(MaterialModificationImage::class)."' , '/', material_modification_image.name)

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
		")
            ->addGroupBy('material_variation_image.ext')
            ->addGroupBy('material_offer_images.ext')
            ->addGroupBy('material_photo.ext');


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
		")
            ->addGroupBy('material_variation_image.cdn')
            ->addGroupBy('material_offer_images.cdn')
            ->addGroupBy('material_photo.cdn');

        /* Категория */
        $dbal->leftJoin(
            'material_event',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material_event.id AND material_event_category.root = true'
        );


        if($this->filter->getCategory())
        {
            $dbal->andWhere('material_event_category.category = :category');
            $dbal->setParameter('category', $this->filter->getCategory(), CategoryMaterialUid::TYPE);
        }

        $dbal->leftJoin(
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


        $dbal->addSelect("
			COALESCE(
                NULLIF(COUNT(material_modification), 0),
                NULLIF(COUNT(material_variation), 0),
                NULLIF(COUNT(material_offer), 0),
                0
            ) AS offer_count
		");


        if($this->search->getQuery())
        {

            /** Поиск по сырья */
            $result = $this->elasticGetIndex ? $this->elasticGetIndex->handle(Material::class, $this->search->getQuery(), 1) : false;

            if($result)
            {
                $counter = $result['hits']['total']['value'];

                if($counter)
                {
                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $dbal
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('material.id', array_column($data, "id"));

                    return $this->paginator->fetchAllAssociative($dbal);
                }
            }


            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('material.id')
                ->addSearchEqualUid('material.event')
                ->addSearchEqualUid('material_variation.id')
                ->addSearchEqualUid('material_modification.id')
                ->addSearchLike('material_trans.name')
                ->addSearchLike('material_info.article')
                ->addSearchLike('material_offer.article')
                ->addSearchLike('material_modification.article')
                ->addSearchLike('material_modification.article')
                ->addSearchLike('material_variation.article');

        }

        $dbal->orderBy('material.event', 'DESC');

        $dbal->allGroupByExclude();

        //$dbal->enableCache('materials-catalog')->fetchAllAssociative();

        return $this->paginator->fetchAllAssociative($dbal);

    }
}
