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

namespace BaksDev\Materials\Catalog\Forms\MaterialFilter\Admin;

use BaksDev\Core\Services\Fields\FieldsChoice;
use BaksDev\Materials\Category\Repository\CategoryChoice\CategoryMaterialChoiceInterface;
use BaksDev\Materials\Category\Repository\ModificationFieldsCategoryChoice\ModificationFieldsCategoryMaterialChoiceInterface;
use BaksDev\Materials\Category\Repository\OfferFieldsCategoryChoice\OfferFieldsCategoryMaterialChoiceInterface;
use BaksDev\Materials\Category\Repository\VariationFieldsCategoryChoice\CategoryMaterialVariationFieldsChoiceInterface;
use BaksDev\Materials\Category\Type\Id\CategoryMaterialUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MaterialFilterForm extends AbstractType
{
    private SessionInterface|false $session = false;

    private string $sessionKey;


    public function __construct(
        private readonly RequestStack $request,
        private readonly CategoryMaterialChoiceInterface $categoryChoice,
        private readonly OfferFieldsCategoryMaterialChoiceInterface $offerChoice,
        private readonly CategoryMaterialVariationFieldsChoiceInterface $variationChoice,
        private readonly ModificationFieldsCategoryMaterialChoiceInterface $modificationChoice,
        private readonly FieldsChoice $choice,
    )
    {
        $this->sessionKey = md5(self::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('all', CheckboxType::class);

        /**
         * Категория
         */

        $builder->add('category', HiddenType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event): void {

            /** @var MaterialFilterDTO $data */
            $data = $event->getData();
            $builder = $event->getForm();

            if($this->session === false)
            {
                $this->session = $this->request->getSession();
            }

            if($this->session && $this->session->get('statusCode') === 307)
            {
                $this->session->remove($this->sessionKey);
                $this->session = false;
            }

            if($this->session && (time() - $this->session->getMetadataBag()->getLastUsed()) > 300)
            {
                $this->session->remove($this->sessionKey);
                $this->session = false;
            }

            if($data->isAllVisible() === false)
            {
                $builder->remove('all');
            }

            if($this->session)
            {
                $sessionData = $this->request->getSession()->get($this->sessionKey);
                $sessionJson = $sessionData ? base64_decode($sessionData) : false;
                $sessionArray = $sessionJson !== false && json_validate($sessionJson) ? json_decode($sessionJson, true, 512, JSON_THROW_ON_ERROR) : false;

                if($sessionArray !== false)
                {
                    !isset($sessionArray['all']) ?: $data->setAll($sessionArray['all'] === true);
                    !isset($sessionArray['category']) ?: $data->setCategory(new CategoryMaterialUid($sessionArray['category'], $sessionArray['category_name'] ?? null));
                    !isset($sessionArray['offer']) ?: $data->setOffer($sessionArray['offer']);
                    !isset($sessionArray['variation']) ?: $data->setVariation($sessionArray['variation']);
                    !isset($sessionArray['modification']) ?: $data->setModification($sessionArray['modification']);
                }
            }

            /** Если жестко не указана категория - выводим список для выбора */

            if($data && $data->isInvisible() === false)
            {
                $builder->add('category', ChoiceType::class, [
                    'choices' => $this->categoryChoice->findAll(),
                    'choice_value' => function(?CategoryMaterialUid $category) {
                        return $category?->getValue();
                    },
                    'choice_label' => function(CategoryMaterialUid $category) {
                        return (is_int($category->getAttr()) ? str_repeat(' - ', $category->getAttr() - 1) : '').$category->getOptions();
                    },
                    'label' => false,
                    'required' => false,
                ]);
            }

            //

            // this would be your entity, i.e. SportMeetup

            /** @var MaterialFilterDTO $data */

            $data = $event->getData();
            $builder = $event->getForm();

            $Category = $data->getCategory();
            $dataRequest = $this->request->getMainRequest()?->get($builder->getName());

            if(isset($dataRequest['category']))
            {
                $Category = empty($dataRequest['category']) ? null : new CategoryMaterialUid($dataRequest['category']);
            }

            if($Category)
            {
                /** Торговое предложение раздела */

                $offerField = $this->offerChoice
                    ->category($Category)
                    ->findAllCategoryMaterialOffers();

                if($offerField)
                {
                    $inputOffer = $this->choice->getChoice($offerField->getField());

                    if($inputOffer)
                    {
                        $builder->add(
                            'offer',
                            method_exists($inputOffer, 'formFilterExists') ? $inputOffer->formFilterExists() : $inputOffer->form(),
                            [
                                'label' => $offerField->getOption(),
                                'priority' => 200,
                                'required' => false,
                                'translation_domain' => $inputOffer->domain()
                            ]
                        );


                        /** Множественные варианты торгового предложения */

                        $variationField = $this->variationChoice
                            ->offer($offerField)
                            ->find();

                        if($variationField)
                        {

                            $inputVariation = $this->choice->getChoice($variationField->getField());

                            if($inputVariation)
                            {
                                $builder->add(
                                    'variation',
                                    method_exists($inputVariation, 'formFilterExists') ? $inputVariation->formFilterExists() : $inputVariation->form(),
                                    [
                                        'label' => $variationField->getOption(),
                                        'priority' => 199,
                                        'required' => false,
                                    ]
                                );

                                /** Модификации множественных вариантов торгового предложения */

                                $modificationField = $this->modificationChoice
                                    ->variation($variationField)
                                    ->findAllModification();


                                if($modificationField)
                                {
                                    $inputModification = $this->choice->getChoice($modificationField->getField());

                                    if($inputModification)
                                    {
                                        $builder->add(
                                            'modification',
                                            method_exists($inputModification, 'formFilterExists') ? $inputModification->formFilterExists() : $inputModification->form(),
                                            [
                                                'label' => $modificationField->getOption(),
                                                'priority' => 198,
                                                'required' => false,
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                //                $fields = $this->fields
                //                    ->category($Category)
                //                    ->findAll();
                //
                //                if($fields)
                //                {
                //                    foreach($fields as $field)
                //                    {
                //                        if(empty($field['const']))
                //                        {
                //                            continue;
                //                        }
                //
                //                        $MaterialFilterPropertyDTO = new Property\MaterialFilterPropertyDTO();
                //
                //                        $MaterialFilterPropertyDTO->setConst($field['const']);
                //                        $MaterialFilterPropertyDTO->setLabel($field['name']);
                //                        $MaterialFilterPropertyDTO->setType($field['type']);
                //
                //                        $MaterialFilterPropertyDTO->setValue($sessionArray['properties'][$field['const']] ?? null);
                //
                //                        $data->addProperty($MaterialFilterPropertyDTO);
                //
                //                    }
                //                }


                /* TRANS CollectionType */
                //                $builder->add('property', CollectionType::class, [
                //                    'entry_type' => Property\MaterialFilterPropertyForm::class,
                //                    'entry_options' => ['label' => false],
                //                    'label' => false,
                //                    'by_reference' => false,
                //                    'allow_delete' => false,
                //                    'allow_add' => false,
                //                    'prototype_name' => '__property__',
                //                ]);


            }
            else
            {
                $data->setOffer(null);
                $data->setVariation(null);
                $data->setModification(null);
            }


        });


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                if($this->session === false)
                {
                    $this->session = $this->request->getSession();
                }

                if($this->session)
                {
                    /** @var MaterialFilterDTO $data */
                    $data = $event->getData();

                    $sessionArray = [];
                    $sessionArray['all'] = $data->getAll();

                    if($data->getCategory())
                    {
                        if($data->getCategory())
                        {
                            $sessionArray['category'] = (string) $data->getCategory();
                            $sessionArray['category_name'] = $data->getCategory()->getOptions();
                        }


                        $data->getOffer() ? $sessionArray['offer'] = (string) $data->getOffer() : false;
                        $data->getVariation() ? $sessionArray['variation'] = (string) $data->getVariation() : false;
                        $data->getModification() ? $sessionArray['modification'] = (string) $data->getModification() : false;
                    }

                    // $properties = [];
                    //
                    //                    if($data->getProperty())
                    //                    {
                    //                        /** @var Property\MaterialFilterPropertyDTO $property */
                    //                        foreach($data->getProperty() as $property)
                    //                        {
                    //                            if(!empty($property->getValue()) && $property->getValue() !== 'false')
                    //                            {
                    //                                $properties[$property->getConst()] = $property->getValue();
                    //                            }
                    //                        }
                    //
                    //                    }

                    //!empty($properties) ? $sessionArray['properties'] = $properties : false;


                    if($sessionArray)
                    {
                        $sessionJson = json_encode($sessionArray, JSON_THROW_ON_ERROR);
                        $sessionData = base64_encode($sessionJson);
                        $this->request->getSession()->set($this->sessionKey, $sessionData);
                        return;
                    }

                    $this->session->remove($this->sessionKey);
                }


                //                $session = [];
                //
                //                if($data->getProperty())
                //                {
                //                    /** @var Property\MaterialFilterPropertyDTO $property */
                //                    foreach($data->getProperty() as $property)
                //                    {
                //                        if(!empty($property->getValue()) && $property->getValue() !== 'false')
                //                        {
                //                            $session[$property->getConst()] = $property->getValue();
                //                        }
                //                    }
                //
                //                }


                // $this->request->getSession()->set('catalog_filter', $session);
            }
        );


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event): void {}
        );


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MaterialFilterDTO::class,
                'validation_groups' => false,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
            ]
        );
    }
}
