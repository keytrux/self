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
    const PRODUCT_SEARCH_URL = @json(route('products.search'));

    function initIngredientProductSearch(row) {
        const input = row.querySelector('.ingredient-product-search');
        const hidden = row.querySelector('.ingredient-product-id');
        const suggestions = row.querySelector('.ingredient-product-suggestions');
        const newFields = row.querySelector('.new-product-fields');

        if (!input || !hidden || !suggestions) {
            return;
        }

        let timer = null;
        let activeIndex = -1;

        function render(items) {
            suggestions.innerHTML = '';

            const newButton = document.createElement('button');
            newButton.type = 'button';
            newButton.textContent = '🆕 Создать новый продукт';
            newButton.className = 'w-full px-3 py-2 text-left text-sm text-blue-600 hover:bg-blue-50 border-b border-gray-100';
            newButton.addEventListener('click', () => {
                hidden.value = 'new';
                input.value = '';
                newFields.classList.remove('hidden');
                suggestions.classList.add('hidden');
                suggestions.innerHTML = '';
                input.focus();
            });
            suggestions.appendChild(newButton);

            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.textContent = 'Ничего не найдено';
                empty.className = 'px-3 py-2 text-sm text-gray-500';
                suggestions.appendChild(empty);
            }

            items.forEach((product) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.productId = product.id;
                button.dataset.productName = product.name;
                button.className = 'w-full px-3 py-2 text-left text-sm hover:bg-gray-100 flex items-baseline justify-between gap-2';
                button.innerHTML = `
                    <span class="text-gray-900">${product.name}${product.brand ? ' <span class="text-gray-500">— ' + product.brand + '</span>' : ''}</span>
                    <span class="text-xs text-gray-400 whitespace-nowrap">${Number(product.calories).toFixed(1)} ккал</span>
                `;
                button.addEventListener('click', () => selectProduct(product.id, product.name));
                suggestions.appendChild(button);
            });

            activeIndex = -1;
        }

        function selectProduct(id, name) {
            hidden.value = id;
            input.value = name;
            newFields.classList.add('hidden');
            suggestions.classList.add('hidden');
            suggestions.innerHTML = '';
            input.focus();
        }

        function fetchSuggestions(query) {
            const url = new URL(PRODUCT_SEARCH_URL, window.location.origin);
            if (query) {
                url.searchParams.set('q', query);
            }

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((response) => response.json())
                .then((data) => {
                    if (document.activeElement === input) {
                        render(data);
                        suggestions.classList.remove('hidden');
                    }
                })
                .catch(() => {
                    suggestions.classList.add('hidden');
                });
        }

        input.addEventListener('input', () => {
            if (hidden.value !== 'new' && hidden.value !== '') {
                hidden.value = '';
            }

            clearTimeout(timer);
            timer = setTimeout(() => fetchSuggestions(input.value.trim()), 300);
        });

        input.addEventListener('focus', () => {
            clearTimeout(timer);
            timer = setTimeout(() => fetchSuggestions(input.value.trim()), 150);
        });

        input.addEventListener('keydown', (e) => {
            const items = suggestions.querySelectorAll('button[data-product-id]');
            const newButton = suggestions.querySelector('button');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length + 1);
                highlight(items, newButton);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                highlight(items, newButton);
            } else if (e.key === 'Enter') {
                const highlighted = suggestions.querySelector('.bg-blue-50');
                if (highlighted) {
                    e.preventDefault();
                    highlighted.click();
                }
            } else if (e.key === 'Escape') {
                suggestions.classList.add('hidden');
            }
        });

        function highlight(items, newButton) {
            const scrollables = [newButton, ...items];
            scrollables.forEach((el, i) => {
                if (el) {
                    el.classList.toggle('bg-blue-50', i === activeIndex);
                }
            });

            const target = scrollables[activeIndex];
            if (target) {
                target.scrollIntoView({ block: 'nearest' });
            }
        }

        document.addEventListener('click', (e) => {
            if (!row.contains(e.target)) {
                suggestions.classList.add('hidden');
            }
        });
    }

    function initIngredientRows() {
        document.querySelectorAll('.ingredient-row').forEach((row) => {
            initIngredientProductSearch(row);
        });
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

        const suggestions = clone.querySelector('.ingredient-product-suggestions');
        if (suggestions) {
            suggestions.classList.add('hidden');
            suggestions.innerHTML = '';
        }

        initIngredientProductSearch(clone);

        return clone;
    }

    function addIngredientRow() {
        const container = document.getElementById('ingredients-container');
        container.prepend(makeIngredientRow());
    }

    function removeIngredientRow(button) {
        const container = document.getElementById('ingredients-container');
        const rows = container.querySelectorAll('.ingredient-row');
        if (rows.length > 1) {
            button.closest('.ingredient-row').remove();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initIngredientRows();
    });
</script>