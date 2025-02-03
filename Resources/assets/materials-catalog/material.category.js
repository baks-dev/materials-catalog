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


/** Коллекция КАТЕГОРИЙ */

/* кнопка Добавить КАТЕГОРИЮ */
let $addButtonCategory = document.getElementById('category_addCollection');

/* Блок для новой коллекции КАТЕГОРИИ */
let $blockCollectionCategory = document.getElementById('category_collection');

if($addButtonCategory)
{
    /* добавить событие на удаление ко всем существующим элементам формы в блок с классом .del-item */
    let $delItemCategory = $blockCollectionCategory.querySelectorAll('.del-item-category');

    /* Удаляем при клике колекцию СЕКЦИЙ */
    $delItemCategory.forEach(function(item)
    {
        item.addEventListener('click', function()
        {

            let $counter = $blockCollectionCategory.getElementsByClassName('item-collection-category').length;
            if($counter > 1)
            {
                item.closest('.item-collection-category').remove();
            }
        });
    });

    /* получаем количество коллекций и присваиваем data-index прототипу */
    $blockCollectionCategory.dataset.index = $blockCollectionCategory.getElementsByClassName('item-collection-category').length.toString();

    /* Добавляем новую коллекцию */
    $addButtonCategory.addEventListener('click', function()
    {
        /* получаем прототип коллекции  */
        let $addButtonCategory = this;

        let newForm = document.getElementById($addButtonCategory.dataset.prototype).dataset.prototype;

        let index = $addButtonCategory.dataset.index * 1;

        /* Замена '__name__' в HTML-коде прототипа
         вместо этого будет число, основанное на том, сколько коллекций */
        newForm = newForm.replace(/__categories__/g, index);
        //newForm = newForm.replace(/__FIELD__/g, index);

        /* Вставляем новую коллекцию */
        let div = document.createElement('div');
        div.classList.add('item-collection-category');
        div.classList.add('d-flex');
        div.classList.add('justify-content-between');
        div.classList.add('align-items-center');
        div.classList.add('gap-3');
        div.innerHTML = newForm;
        $blockCollectionCategory.append(div);

        /* Удаляем при клике колекцию СЕКЦИЙ */
        div.querySelector('.del-item-category').addEventListener('click', function()
        {
            let $counter = $blockCollectionCategory.getElementsByClassName('item-collection-category').length;
            if($counter > 1)
            {
                this.closest('.item-collection-category').remove();
                index = $addButtonCategory.dataset.index * 1;
                $addButtonCategory.dataset.index = (index - 1).toString();
            }
        });

        /* Увеличиваем data-index на 1 после вставки новой коллекции */
        $addButtonCategory.dataset.index = (index + 1).toString();

        /* применяем select2 */
        new NiceSelect(div.querySelector('[data-select="select2"]'), {searchable: true});

    });
}

$select2Category = document.getElementById('material_form_category_0_category');
//new NiceSelect($select2Category, {searchable: true});


/* ИЗМЕНЕНИЕ СВОЙСТВ В ЗАВИСИМОСТИ ОТ КАТЕГОРИИ */
//let attr = document.querySelector('[data-parent="true"]');

$select2Category.addEventListener('change', function()
{
    window.location.href = '?category=' + (this.value ? this.value : 'null');
});

