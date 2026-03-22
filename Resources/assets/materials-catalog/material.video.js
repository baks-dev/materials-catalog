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


/** коллекция ВИДЕО */

let $addButtonVideo = document.getElementById("video_addCollection");

/* Блок для новой коллекции */
let $blockCollectionVideo = document.getElementById("video_collection");

if($addButtonVideo)
{
    /* добавить событие на удаление ко всем существующим элементам формы в блок с классом .del-item */
    let $delItemVideo = $blockCollectionVideo.querySelectorAll(".del-item-video");

    /* Удаляем при клике колекцию СЕКЦИЙ */
    $delItemVideo.forEach(function(item)
    {
        item.addEventListener("click", function()
        {

            item.closest(".item-collection-video").remove();

        });
    });

    /* получаем количество коллекций и присваиваем data-index прототипу */
    //$blockCollectionVideo.dataset.index = $blockCollectionVideo.getElementsByClassName('item-collection-video').length.toString();


    /* Добавляем новую коллекцию */
    $addButtonVideo.addEventListener("click", function()
    {

        let $addButtonVideo = this;

        /* получаем прототип коллекции  */
        //let newForm = $addButtonVideo.dataset.prototype;
        let newForm = document.getElementById($addButtonVideo.dataset.prototype).dataset.prototype;
        let index = $addButtonVideo.dataset.index * 1;

        /* Замена '__name__' в HTML-коде прототипа на
         вместо этого будет число, основанное на том, сколько коллекций */
        newForm = newForm.replace(/__videos__/g, index);


        /* Вставляем новую коллекцию */
        let div = document.createElement("div");
        div.classList.add("d-flex");
        div.classList.add("justify-content-between");
        div.classList.add("align-items-center");
        div.classList.add("gap-3");
        div.classList.add("item-collection-video");


        div.innerHTML = newForm;
        $blockCollectionVideo.append(div);

        /* Удаляем при клике колекцию СЕКЦИЙ */
        div.querySelector(".del-item-video").addEventListener("click", function()
        {
            this.closest(".item-collection-video").remove();
            let index = $addButtonVideo.dataset.index * 1;
            $addButtonVideo.dataset.index = (index - 1).toString();
        });

        $addButtonVideo.dataset.index = (index + 1).toString();

        /* Увеличиваем data-index на 1 после вставки новой коллекции */
        //$blockCollectionVideo.dataset.index = index.toString();

    });
}