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


/** Коллекция ФАЙЛОВ */

let $addButtonFile = document.getElementById("file_addCollection");

/* Блок для новой коллекции */
let $blockCollectionFile = document.getElementById("file_collection");

if($addButtonFile)
{
    /* добавить событие на удаление ко всем существующим элементам формы в блок с классом .del-item */
    let $delItemFile = $blockCollectionFile.querySelectorAll(".del-item-file");

    /* Удаляем при клике колекцию СЕКЦИЙ */
    $delItemFile.forEach(function(item)
    {
        item.addEventListener("click", function()
        {
            item.closest(".item-collection-file").remove();
        });
    });

    /* получаем количество коллекций и присваиваем data-index прототипу */
    //$blockCollectionFile.dataset.index = $blockCollectionFile.getElementsByClassName('item-collection-file').length.toString();

    /* Добавляем новую коллекцию */
    $addButtonFile.addEventListener("click", function()
    {

        let $addButtonFile = this;

        /* получаем прототип коллекции  */
        let newForm = document.getElementById($addButtonFile.dataset.prototype).dataset.prototype;
        let index = $addButtonFile.dataset.index * 1;

        /* Замена '__name__' в HTML-коде прототипа
         вместо этого будет число, основанное на том, сколько коллекций */
        newForm = newForm.replace(/__files__/g, index);
        //newForm = newForm.replace(/__FIELD__/g, index);

        /* Вставляем новую коллекцию */
        let div = document.createElement("div");
        div.classList.add("d-flex");
        div.classList.add("justify-content-between");
        div.classList.add("align-items-center");
        div.classList.add("gap-3");
        div.classList.add("item-collection-file");

        div.innerHTML = newForm;
        $blockCollectionFile.append(div);

        /* Удаляем при клике колекцию СЕКЦИЙ */
        div.querySelector(".del-item-file").addEventListener("click", function()
        {
            this.closest(".item-collection-file").remove();
            let index = $addButtonFile.dataset.index * 1;
            $addButtonFile.dataset.index = (index - 1).toString();
        });

        $addButtonFile.dataset.index = (index + 1).toString();

        /* Увеличиваем data-index на 1 после вставки новой коллекции */
        //$blockCollectionFile.dataset.index = index.toString();

    });
}
