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

namespace BaksDev\Materials\Catalog\UseCase\Admin\NewEdit\Property;

use BaksDev\Core\Services\Fields\FieldsChoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/* Форма свойств продукта */


final class PropertyCollectionForm extends AbstractType
{
    public function __construct(private readonly FieldsChoice $fieldsChoice) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($options) {
                $data = $event->getData();
                $form = $event->getForm();

                if($data)
                {
                    $propertyCategory = $options['properties'];
                    $id = (string) $data->getField();

                    if(empty($id) || !isset($propertyCategory[$id]))
                    {
                        return;
                    }

                    $propCat = $propertyCategory[$id];

                    /* СЕКЦИЯ для определения названии в рендере */
                    $form->add(
                        'section',
                        HiddenType::class,
                        [
                            //'mapped' => false,
                            //'data' => (string) $propCat->sectionUid,
                            'label' => $propCat->sectionTrans,
                        ]
                    );

                    $fieldType = $this->fieldsChoice->getChoice($propCat->fieldType);

                    $form->add(
                        'value',
                        $fieldType ? $fieldType->form() : HiddenType::class,
                        [
                            'label' => $propCat->fieldTrans,
                            'required' => $propCat->fieldRequired,
                        ]
                    );

                }
            }
        );

    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PropertyCollectionDTO::class,
                'properties' => null,
            ]
        );
    }

}
