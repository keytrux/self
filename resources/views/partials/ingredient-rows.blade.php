@php
    $required = $required ?? false;

    if (old('ingredients') !== null) {
        $iterations = old('ingredients');
    } elseif (isset($ingredients)) {
        $iterations = $ingredients;
    } else {
        $iterations = collect([[]]);
    }
@endphp

<div id="ingredients-container" class="space-y-3">
    @foreach ($iterations as $index => $ingredient)
        @include('partials.ingredient-row', [
            'index' => $index,
            'ingredient' => $ingredient,
            'products' => $products,
            'required' => $required,
        ])
    @endforeach
</div>

<template id="ingredient-row-template">
    @include('partials.ingredient-row', [
        'index' => 0,
        'ingredient' => null,
        'products' => $products,
        'required' => $required,
    ])
</template>

<script>
    function toggleNewProductFields(select) {
        const row = select.closest('.ingredient-row');
        const fields = row.querySelector('.new-product-fields');
        if (fields) {
            fields.classList.toggle('hidden', select.value !== 'new');
        }
    }

    function makeIngredientRow() {
        const template = document.getElementById('ingredient-row-template');
        const clone = template.content.firstElementChild.cloneNode(true);
        let index = 0;

        document.querySelectorAll('#ingredients-container .ingredient-row [name^="ingredients["]').forEach((el) => {
            const match = el.getAttribute('name').match(/ingredients\[(\d+)\]/);
            if (match) {
                index = Math.max(index, Number(match[1]) + 1);
            }
        });

        clone.querySelectorAll('[name^="ingredients["]').forEach((el) => {
            const name = el.getAttribute('name');
            if (name) {
                el.setAttribute('name', name.replace(/ingredients\[\d+\]/, `ingredients[${index}]`));
            }
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });

        const newFields = clone.querySelector('.new-product-fields');
        if (newFields) {
            newFields.classList.add('hidden');
        }

        return clone;
    }

    function addIngredientRow() {
        const container = document.getElementById('ingredients-container');
        container.appendChild(makeIngredientRow());
    }

    function removeIngredientRow(button) {
        const container = document.getElementById('ingredients-container');
        const rows = container.querySelectorAll('.ingredient-row');
        if (rows.length > 1) {
            button.closest('.ingredient-row').remove();
        }
    }

    document.querySelectorAll('.ingredient-product-select').forEach((select) => {
        toggleNewProductFields(select);
    });
</script>