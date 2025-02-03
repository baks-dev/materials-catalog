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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Price;

use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Measurement\Type\Measurement;
use BaksDev\Reference\Money\Type\Money;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MaterialPriceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add(
            'price',
            MoneyType::class,
            ['currency' => false, 'required' => false]
        );

        //        $builder->add(
        //            'old',
        //            MoneyType::class,
        //            ['currency' => false, 'required' => false]
        //        );

        $builder->get('price')->addModelTransformer(
            new CallbackTransformer(
                function($price) {
                    return $price instanceof Money ? $price->getValue() : $price;
                },
                function($price) {

                    return new Money($price);
                }
            )
        );

        $builder->add(
            'currency',
            ChoiceType::class,
            [
                'choices' => Currency::cases(),
                'choice_value' => function(?Currency $currency) {
                    return $currency?->getCurrencyValue();
                },
                'choice_label' => function(?Currency $currency) {
                    return $currency?->getCurrencyValue();
                },
                'translation_domain' => 'reference.currency',
                'label' => false,
            ]
        );

        /* Цена по запросу */
        $builder->add('request', CheckboxType::class, ['label' => false, 'required' => false]);

        /* Количество В наличии */
        //$builder->add('quantity', IntegerType::class, ['required' => false]);

        /* Зарезервирован */
        //$builder->add('reserve', IntegerType::class, ['required' => false]);

        /* Единица измерения */
        /*$builder
            ->add('measurement', ChoiceType::class, [
                'choices' => Measurement::cases(),
                'choice_value' => function (?Measurement $measurement) {
                    return $measurement?->getMeasurementValue();
                },
                'choice_label' => function (?Measurement $measurement) {
                    return $measurement?->getMeasurementValue();
                },
                'choice_translation_domain' => 'reference.measurement',

                'label' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ]);*/

    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaterialPriceDTO::class,
        ]);
    }

}
