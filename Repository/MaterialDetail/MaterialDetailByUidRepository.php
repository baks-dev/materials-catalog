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

namespace BaksDev\Materials\Catalog\Repository\MaterialDetail;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Catalog\Entity\Active\MaterialActive;
use BaksDev\Materials\Catalog\Entity\Category\MaterialCategory;
use BaksDev\Materials\Catalog\Entity\Description\MaterialDescription;
use BaksDev\Materials\Catalog\Entity\Event\MaterialEvent;
use BaksDev\Materials\Catalog\Entity\Info\MaterialInfo;
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
use BaksDev\Materials\Catalog\Entity\Property\MaterialProperty;
use BaksDev\Materials\Catalog\Entity\Trans\MaterialTrans;
use BaksDev\Materials\Catalog\Type\Event\MaterialEventUid;
use BaksDev\Materials\Catalog\Type\Offers\Id\MaterialOfferUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Id\MaterialVariationUid;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\Id\MaterialModificationUid;
use BaksDev\Materials\Category\Entity\CategoryMaterial;
use BaksDev\Materials\Category\Entity\Info\CategoryMaterialInfo;
use BaksDev\Materials\Category\Entity\Offers\CategoryMaterialOffers;
use BaksDev\Materials\Category\Entity\Offers\Trans\CategoryMaterialOffersTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\CategoryMaterialVariation;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\CategoryMaterialModification;
use BaksDev\Materials\Category\Entity\Offers\Variation\Modification\Trans\CategoryMaterialModificationTrans;
use BaksDev\Materials\Category\Entity\Offers\Variation\Trans\CategoryMaterialVariationTrans;
use BaksDev\Materials\Category\Entity\Section\CategoryMaterialSection;
use BaksDev\Materials\Category\Entity\Section\Field\CategoryMaterialSectionField;
use BaksDev\Materials\Category\Entity\Section\Field\Trans\CategoryMaterialSectionFieldTrans;
use BaksDev\Materials\Category\Entity\Trans\CategoryMaterialTrans;
use InvalidArgumentException;

final class MaterialDetailByUidRepository implements MaterialDetailByUidInterface
{
    private MaterialEventUid|false $event = false;

    private MaterialOfferUid|false $offer = false;

    private MaterialVariationUid|false $variation = false;

    private MaterialModificationUid|false $modification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function event(MaterialEvent|MaterialEventUid|string $event): self
    {
        if(is_string($event))
        {
            $event = new MaterialEventUid($event);
        }

        if($event instanceof MaterialEvent)
        {
            $event = $event->getId();
        }

        $this->event = $event;
        return $this;
    }

    public function offer(MaterialOffer|MaterialOfferUid|string|null|false $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new MaterialOfferUid($offer);
        }

        if($offer instanceof MaterialOffer)
        {
            $offer = $offer->getId();
        }

        $this->offer = $offer;
        return $this;
    }

    public function variation(MaterialVariation|MaterialVariationUid|string|null|false $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new MaterialVariationUid($variation);
        }

        if($variation instanceof MaterialVariation)
        {
            $variation = $variation->getId();
        }

        $this->variation = $variation;
        return $this;
    }

    public function modification(MaterialModification|MaterialModificationUid|string|null|false $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new MaterialModificationUid($modification);
        }

        if($modification instanceof MaterialModification)
        {
            $modification = $modification->getId();
        }

        $this->modification = $modification;
        return $this;
    }

    /**
     * Метод возвращает детальную информацию о сырье по его идентификаторам события, ТП, вариантов и модификаций.
     */
    public function find(): array|false
    {

        if(false === $this->event)
        {
            throw new InvalidArgumentException('Invalid Argument MaterialEvent');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal->select('material_event.main'); //->groupBy('material_event.main');
        $dbal->addSelect('material_event.id'); //->addGroupBy('material_event.id');

        $dbal
            ->from(MaterialEvent::class, 'material_event')
            ->where('material_event.id = :event')
            ->setParameter(
                'event',
                $this->event,
                MaterialEventUid::TYPE
            );


        $dbal
            ->addSelect('material_active.active')
            ->addSelect('material_active.active_from')
            ->addSelect('material_active.active_to')
            ->join(
                'material_event',
                MaterialActive::class,
                'material_active',
                'material_active.event = material_event.id'
            );

        $dbal
            ->addSelect('material_trans.name AS material_name')
            ->leftJoin(
                'material_event',
                MaterialTrans::class,
                'material_trans',
                'material_trans.event = material_event.id AND material_trans.local = :local'
            );


        $dbal
            ->addSelect('material_desc.preview AS material_preview')
            ->addSelect('material_desc.description AS material_description')
            ->leftJoin(
                'material_event',
                MaterialDescription::class,
                'material_desc',
                'material_desc.event = material_event.id AND material_desc.device = :device '
            )
            ->setParameter('device', 'pc');

        /* Базовая Цена товара */
        $dbal->leftJoin(
            'material_event',
            MaterialPrice::class,
            'material_price',
            'material_price.event = material_event.id'
        );

        /* MaterialInfo */

        $dbal
            ->leftJoin(
                'material_event',
                MaterialInfo::class,
                'material_info',
                'material_info.material = material_event.main '
            );

        /* Торговое предложение */

        $dbal
            ->addSelect('material_offer.id as material_offer_uid')
            ->addSelect('material_offer.value as material_offer_value');

        $dbal->{$this->offer ? 'join' : 'leftJoin'}(
            'material_event',
            MaterialOffer::class,
            'material_offer',
            'material_offer.event = material_event.id '.($this->offer ? ' AND material_offer.id = :material_offer' : '').' '
        );

        if($this->offer)
        {
            $dbal->setParameter('material_offer', $this->offer, MaterialOfferUid::TYPE);
        }

        /* Цена торгового предоложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferPrice::class,
            'material_offer_price',
            'material_offer_price.offer = material_offer.id'
        );

        /* Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS material_offer_reference')
            ->leftJoin(
                'material_offer',
                CategoryMaterialOffers::class,
                'category_offer',
                'category_offer.id = material_offer.category_offer'
            );

        /* Получаем название торгового предложения */
        $dbal
            ->addSelect('category_offer_trans.name as material_offer_name')
            ->leftJoin(
                'category_offer',
                CategoryMaterialOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );

        /* Наличие и резерв торгового предложения */
        $dbal->leftJoin(
            'material_offer',
            MaterialOfferQuantity::class,
            'material_offer_quantity',
            'material_offer_quantity.offer = material_offer.id'
        );

        //MaterialCategoryOffers

        /* Множественные варианты торгового предложения */

        $dbal
            ->addSelect('material_variation.id as material_variation_uid')
            ->addSelect('material_variation.value as material_variation_value');

        $dbal->{$this->variation ? 'join' : 'leftJoin'}(
            'material_offer',
            MaterialVariation::class,
            'material_variation',
            'material_variation.offer = material_offer.id'.($this->variation ? ' AND material_variation.id = :material_variation' : '').' '
        );

        if($this->variation)
        {
            $dbal->setParameter('material_variation', $this->variation, MaterialVariationUid::TYPE);
        }

        /* Цена множественного варианта */
        $dbal->leftJoin(
            'material_variation',
            MaterialVariationPrice::class,
            'material_variation_price',
            'material_variation_price.variation = material_variation.id'
        );

        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_offer_variation.reference as material_variation_reference')
            ->leftJoin(
                'material_variation',
                CategoryMaterialVariation::class,
                'category_offer_variation',
                'category_offer_variation.id = material_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_offer_variation_trans.name as material_variation_name')
            ->leftJoin(
                'category_offer_variation',
                CategoryMaterialVariationTrans::class,
                'category_offer_variation_trans',
                'category_offer_variation_trans.variation = category_offer_variation.id AND category_offer_variation_trans.local = :local'
            );


        /* Наличие и резерв множественного варианта */
        $dbal->leftJoin(
            'category_offer_variation',
            MaterialsVariationQuantity::class,
            'material_variation_quantity',
            'material_variation_quantity.variation = material_variation.id'
        );

        /* Модификация множественного варианта торгового предложения */

        $dbal
            ->addSelect('material_modification.id as material_modification_uid')
            ->addSelect('material_modification.value as material_modification_value');

        $dbal->{$this->modification ? 'join' : 'leftJoin'}(
            'material_variation',
            MaterialModification::class,
            'material_modification',
            'material_modification.variation = material_variation.id'.($this->modification ? ' AND material_modification.id = :material_modification' : '').' '
        );

        if($this->modification)
        {
            $dbal->setParameter('material_modification', $this->modification, MaterialModificationUid::TYPE);
        }

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'material_modification',
            MaterialModificationPrice::class,
            'material_modification_price',
            'material_modification_price.modification = material_modification.id'
        );

        /* Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_offer_modification.reference as material_modification_reference')
            ->leftJoin(
                'material_modification',
                CategoryMaterialModification::class,
                'category_offer_modification',
                'category_offer_modification.id = material_modification.category_modification'
            );

        /* Получаем название типа модификации */
        $dbal
            ->addSelect('category_offer_modification_trans.name as material_modification_name')
            ->leftJoin(
                'category_offer_modification',
                CategoryMaterialModificationTrans::class,
                'category_offer_modification_trans',
                '
            category_offer_modification_trans.modification = category_offer_modification.id AND 
            category_offer_modification_trans.local = :local'
            );

        /* Наличие и резерв модификации множественного варианта */
        $dbal->leftJoin(
            'category_offer_modification',
            MaterialModificationQuantity::class,
            'material_modification_quantity',
            'material_modification_quantity.modification = material_modification.id'
        );


        /* Артикул сырья */

        $dbal->addSelect('
            COALESCE(
                material_modification.article, 
                material_variation.article, 
                material_offer.article, 
                material_info.article
            ) AS material_article
		');


        /* Фото сырья */

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
            'material_variation',
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

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
				WHEN material_modification_image.name IS NOT NULL 
			   THEN material_modification_image.ext
			   
			   WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.ext
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.ext
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.ext
					
			   ELSE NULL
			END AS material_image_ext
		');

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			   WHEN material_modification_image.name IS NOT NULL 
			   THEN material_modification_image.cdn
			   
				WHEN material_variation_image.name IS NOT NULL 
			   THEN material_variation_image.cdn
					
			   WHEN material_offer_images.name IS NOT NULL 
			   THEN material_offer_images.cdn
					
			   WHEN material_photo.name IS NOT NULL 
			   THEN material_photo.cdn
					
			   ELSE NULL
			END AS material_image_cdn
		');


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

        $dbal->addSelect(
            '
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
			END AS material_quantity
		'
        );


        /* Категория */
        $dbal->join(
            'material_event',
            MaterialCategory::class,
            'material_event_category',
            'material_event_category.event = material_event.id AND material_event_category.root = true'
        );


        $dbal->join(
            'material_event_category',
            CategoryMaterial::class,
            'category',
            'category.id = material_event_category.category'
        );

        $dbal->addSelect('category_trans.name AS category_name')->addGroupBy('category_trans.name');

        $dbal->leftJoin(
            'category',
            CategoryMaterialTrans::class,
            'category_trans',
            'category_trans.event = category.event AND category_trans.local = :local'
        );


        $dbal->allGroupByExclude();

        /* Кешируем результат DBAL */
        return $dbal
            ->enableCache('materials-catalog', 86400)
            ->fetchAssociative();

    }
}
