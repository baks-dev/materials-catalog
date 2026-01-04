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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit;

use BaksDev\Core\Services\Reference\ReferenceChoice;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Category\MaterialCategoryCollectionDTO;
use BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Property\PropertyCollectionDTO;
use BaksDev\Materials\Category\Repository\CategoryModificationForm\CategoryMaterialModificationFormInterface;
use BaksDev\Materials\Category\Repository\CategoryOffersForm\CategoryMaterialOffersFormInterface;
use BaksDev\Materials\Category\Repository\CategoryPropertyById\CategoryMaterialPropertyByIdInterface;
use BaksDev\Materials\Category\Repository\CategoryVariationForm\CategoryMaterialVariationFormInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MaterialForm extends AbstractType
{
    public function __construct(
        private readonly CategoryMaterialPropertyByIdInterface $categoryProperty,
        private readonly CategoryMaterialOffersFormInterface $categoryOffers,
        private readonly CategoryMaterialVariationFormInterface $categoryVariation,
        private readonly CategoryMaterialModificationFormInterface $categoryModification,
        private readonly ReferenceChoice $reference,
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('info', Info\MaterialInfoForm::class, ['label' => false]);

        $builder->add('price', Price\MaterialPriceForm::class, ['label' => false]);

        /* CATEGORIES CollectionType */
        $builder->add('category', CollectionType::class, [
            'entry_type' => Category\MaterialCategoryCollectionForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__categories__',
        ]);

        /* FILES Collection */
        $builder->add('file', CollectionType::class, [
            'entry_type' => Files\MaterialFilesCollectionForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__files__',
        ]);


        /* TRANS CollectionType */
        $builder->add('translate', CollectionType::class, [
            'entry_type' => Trans\MaterialTransForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__trans__',
        ]);



        /* PHOTOS CollectionType */
        $builder->add('photo', CollectionType::class, [
            'entry_type' => Photo\MaterialPhotoCollectionForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__photos__',
        ]);

        /* FILES CollectionType */
        $builder->add('file', CollectionType::class, [
            'entry_type' => Files\MaterialFilesCollectionForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__files__',
        ]);



        /*
         * PROPERTIES
        */

        /* @var ArrayCollection $categories */
        $categories = $options['data']->getCategory();
        /* @var MaterialCategoryCollectionDTO $category */
        $category = $categories->current();

        //        $propertyCategory = $category->getCategory() ? $this->categoryProperty
        //            ->forCategory($category->getCategory())
        //            ->findAll() : [];

        /* CollectionType */
        /*$builder->add('property', CollectionType::class, [
            'entry_type' => Property\PropertyCollectionForm::class,
            'entry_options' => [
                'label' => false,
                'properties' => $propertyCategory,
            ],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__properties__',
        ]);*/

        $builder->add('dataOffer', HiddenType::class, ['mapped' => false]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) /*use ($propertyCategory)*/ {

                return;

                /* @var MaterialDTO $data */
                $data = $event->getData();
                $form = $event->getForm();


                if($data && $propertyCategory)
                {
                    $sort = 0;

                    foreach($propertyCategory as $propCat)
                    {
                        $new = true;

                        //                        foreach($data->getProperty() as $fieldProperty)
                        //                        {
                        //
                        //                            /* Если поле уже заполнено - не объявляем */
                        //                            if($propCat->fieldUid->equals($fieldProperty->getField()))
                        //                            {
                        //                                $fieldProperty->setSection($propCat->sectionUid);
                        //                                $fieldProperty->setSort($sort);
                        //
                        //                                $new = false;
                        //                                break;
                        //                            }
                        //
                        //                            /* Удаляем свойства, Которые были удалены из категории */
                        //                            if(!isset($propertyCategory[(string) $fieldProperty->getField()]))
                        //                            {
                        //                                $data->removeProperty($fieldProperty);
                        //                            }
                        //
                        //                        }

                        /* Если поле не заполнено ранее - создаем */
                        if($new)
                        {
                            $Property = new PropertyCollectionDTO();
                            $Property->setField($propCat->fieldUid);
                            $Property->setSection($propCat->sectionUid);
                            $Property->setSort($sort);
                            $data->addProperty($Property);
                        }

                        $sort++;
                    }
                }
            }
        );

        /* Сохранить ******************************************************/
        $builder->add(
            'material',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );

        /*
         * OFFERS
        */

        //$offers = $category ? $this->categoryOffers->get($category->getCategory()) : null; //  $this->getField->get($profileType);

        /** Создаем торговое предложение  */

        /* Получаем Торговые предложения категории */
        $offersCategory = $category->getCategory() ?
            $this->categoryOffers
                ->category($category->getCategory())
                ->findAllOffers() : null;

        /* Получаем множественные варианты ТП */
        $variationCategory = $offersCategory ?
            $this->categoryVariation
                ->offer($offersCategory->id)
                ->findAllVariation() :
            null;

        /* Получаем модификации множественных вариантов */
        $modificationCategory =
            $variationCategory ?
                $this->categoryModification
                    ->variation($variationCategory->id)
                    ->findAllModification() :
                null;

        $builder->add('offer', CollectionType::class, [
            'entry_type' => Offers\MaterialOffersCollectionForm::class,
            'entry_options' => [
                'label' => false,
                //'category_id' => $category,
                'offers' => $offersCategory,
                'variation' => $variationCategory,
                'modification' => $modificationCategory,
            ],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__offers__',
        ]);

        if($offersCategory)
        {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function(FormEvent $event) use ($offersCategory, $variationCategory, $modificationCategory) {

                    /* @var MaterialDTO $data */
                    $data = $event->getData();
                    $form = $event->getForm();


                    if(!empty($offersCategory))
                    {
                        /* Создаем свойство с идентификатором ТП для прототипа */
                        $form->add('dataOffer', HiddenType::class, ['data' => $offersCategory->id, 'mapped' => false]);

                        if($offersCategory->reference)
                        {
                            $reference = $this->reference->getChoice($offersCategory->reference);

                            if($reference)
                            {

                                $form->add(
                                    'data-offer-reference',
                                    $reference->form(),
                                    [
                                        'label' => false,
                                        'required' => false,
                                        'mapped' => false,
                                        'attr' => ['style' => 'display: none;'],
                                    ]
                                );

                            }
                        }
                    }


                    if(!empty($variationCategory))
                    {
                        /* Создаем свойство с идентификатором множественного варианта для прототипа */
                        $form->add(
                            'dataVariation',
                            HiddenType::class,
                            ['data' => $variationCategory->id, 'mapped' => false]
                        );

                        if($variationCategory->reference)
                        {

                            //$form->add('data-offer-reference', HiddenType::class,  ['data' => $offersCategory->id, 'mapped' => false]);

                            $reference = $this->reference->getChoice($variationCategory->reference);

                            if($reference)
                            {
                                $form->add(
                                    'data-variation-reference',
                                    $reference->form(),
                                    [
                                        'label' => false,
                                        'required' => false,
                                        'mapped' => false,
                                        'attr' => ['style' => 'display: none;'],
                                    ]
                                );


                            }
                        }
                    }

                    if(!empty($modificationCategory))
                    {
                        /* Создаем свойство с идентификатором модификации для прототипа */
                        $form->add(
                            'dataModification',
                            HiddenType::class,
                            ['data' => $modificationCategory->id, 'mapped' => false]
                        );

                        if($modificationCategory->reference)
                        {

                            //$form->add('data-offer-reference', HiddenType::class,  ['data' => $offersCategory->id, 'mapped' => false]);

                            $reference = $this->reference->getChoice($modificationCategory->reference);

                            if($reference)
                            {

                                $form->add(
                                    'data-modification-reference',
                                    $reference->form(),
                                    [
                                        'label' => false,
                                        'required' => false,
                                        'mapped' => false,
                                        'attr' => ['style' => 'display: none;'],
                                    ]
                                );


                            }
                        }
                    }


                    if(!empty($offersCategory) && $data->getOffer()->isEmpty())
                    {

                        $MaterialOffersCollectionDTO = new Offers\MaterialOffersCollectionDTO();
                        $MaterialOffersCollectionDTO->setCategoryOffer($offersCategory->id);

                        if($offersCategory->image)
                        {
                            $MaterialOfferImageCollectionDTO = new Offers\Image\MaterialOfferImageCollectionDTO();
                            $MaterialOfferImageCollectionDTO->setRoot(true);
                            $MaterialOffersCollectionDTO->addImage($MaterialOfferImageCollectionDTO);
                        }

                        if($variationCategory)
                        {

                            $MaterialOffersVariationCollectionDTO = new Offers\Variation\MaterialVariationCollectionDTO();
                            $MaterialOffersVariationCollectionDTO->setCategoryVariation($variationCategory->id);

                            if($variationCategory->image)
                            {
                                $MaterialOfferVariationImageCollectionDTO =
                                    new Offers\Variation\Image\MaterialVariationImageCollectionDTO();
                                $MaterialOfferVariationImageCollectionDTO->setRoot(true);
                                $MaterialOffersVariationCollectionDTO->addImage(
                                    $MaterialOfferVariationImageCollectionDTO
                                );
                            }

                            $MaterialOffersCollectionDTO->addVariation($MaterialOffersVariationCollectionDTO);


                            if($modificationCategory)
                            {
                                $MaterialOffersVariationModificationCollectionDTO =
                                    new Offers\Variation\Modification\MaterialModificationCollectionDTO();
                                $MaterialOffersVariationModificationCollectionDTO
                                    ->setCategoryModification($modificationCategory->id);

                                if($modificationCategory->image)
                                {
                                    $MaterialOfferVariationModificationImageCollectionDTO =
                                        new Offers\Variation\Modification\Image\MaterialModificationImageCollectionDTO();
                                    $MaterialOfferVariationModificationImageCollectionDTO->setRoot(true);
                                    $MaterialOffersVariationModificationCollectionDTO->addImage($MaterialOfferVariationModificationImageCollectionDTO);


                                }

                                $MaterialOffersVariationCollectionDTO->addModification($MaterialOffersVariationModificationCollectionDTO);
                            }

                        }

                        $data->addOffer($MaterialOffersCollectionDTO);
                    }
                }
            );
        }


    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MaterialDTO::class,
                'attr' => ['class' => 'w-100'],
            ]
        );
    }

}
