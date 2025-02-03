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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Offers\Variation;

use BaksDev\Core\Services\Reference\ReferenceChoice;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Category\Type\Offers\Variation\CategoryMaterialVariationUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MaterialVariationCollectionForm extends AbstractType
{
    private ReferenceChoice $reference;


    public function __construct(ReferenceChoice $reference)
    {
        $this->reference = $reference;
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $variation = $options['variation'];
        $modification = $options['modification'];

        $builder->add('categoryVariation', HiddenType::class);


        $builder->get('categoryVariation')->addModelTransformer(
            new CallbackTransformer(
                function($categoryVariation) {
                    return $categoryVariation instanceof CategoryMaterialVariationUid ? $categoryVariation->getValue() : $categoryVariation;
                },
                function($categoryVariation) {
                    return new CategoryMaterialVariationUid($categoryVariation);
                }
            )
        );

        $builder->add('const', HiddenType::class);

        $builder->get('const')->addModelTransformer(
            new CallbackTransformer(
                function($const) {
                    return $const instanceof MaterialVariationConst ? $const->getValue() : $const;
                },
                function($const) {
                    return new MaterialVariationConst($const);
                }
            )
        );


        $builder->add('article', TextType::class);

        $builder->add('value', TextType::class, ['label' => $variation->name, 'attr' => ['class' => 'mb-3']]);

        $builder->add('price', Price\MaterialOfferVariationPriceForm::class, ['label' => false]);

        /** Торговые предложения */
        $builder->add('image', CollectionType::class, [
            'entry_type' => Image\MaterialOfferVariationImageCollectionForm::class,
            'entry_options' => [
                'label' => false,
            ],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__variation_image__',
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($variation) {
                $data = $event->getData();
                $form = $event->getForm();

                if($data)
                {

                    /* Получаем данные торговые предложения категории */
                    //$offerCat = $data->getOffer();

                    /* Если ТП - справочник - перобразуем поле ChoiceType   */
                    if($variation->reference)
                    {
                        $reference = $this->reference->getChoice($variation->reference);

                        if($reference)
                        {

                            $form->add(
                                'value',
                                $reference->form(),
                                [
                                    'label' => $variation->name,
                                    'required' => false,
                                    //'mapped' => false,
                                    //'attr' => [ 'data-select' => 'select2' ],
                                ]
                            );

                        }
                    }

                    /* Удаляем количественный учет */
                    if(!$variation->quantitative)
                    {
                        $form->remove('quantity');
                    }

                    /* Удаляем артикул если запрещено */
                    if(!$variation->article)
                    {
                        $form->remove('article');
                    }


                    /* Удаляем пользовательское изображение если запрещено */
                    if(!$variation->image)
                    {
                        $form->remove('image');
                    }

                    /* Удаляем Прайс на торговое предложение, если нет прайса */
                    if(!$variation->price)
                    {
                        $form->remove('price');
                    }
                }
            }
        );


        if($modification)
        {
            /** Множественные варианты торгового предложения */
            $builder->add('modification', CollectionType::class, [
                'entry_type' => Modification\MaterialModificationCollectionForm::class,
                'entry_options' => [
                    'label' => false,
                    'modification' => $modification,
                ],
                'label' => false,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
                'prototype_name' => '__variation_modification__',
            ]);
        }

        $builder->add(
            'DeleteVariation',
            ButtonType::class,
            [
                'label_html' => true,
                'attr' =>
                    ['class' => 'btn btn-sm btn-icon btn-light-danger del-item-variation'],
            ]
        );

    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaterialVariationCollectionDTO::class,
            'variation' => null,
            'modification' => null,
            //'offers' => null,
        ]);
    }

}
