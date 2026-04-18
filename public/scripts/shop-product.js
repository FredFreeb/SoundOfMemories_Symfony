document.addEventListener('DOMContentLoaded', () => {
    const gallery = document.querySelector('[data-product-gallery]');
    const mainImage = document.querySelector('[data-main-product-image]');

    if (gallery && mainImage) {
        gallery.querySelectorAll('[data-product-thumb]').forEach((thumbButton) => {
            thumbButton.addEventListener('click', () => {
                const imageUrl = thumbButton.getAttribute('data-image-url');
                const imageAlt = thumbButton.getAttribute('data-image-alt') || '';

                if (!imageUrl) {
                    return;
                }

                mainImage.setAttribute('src', imageUrl);
                mainImage.setAttribute('alt', imageAlt);

                gallery.querySelectorAll('[data-product-thumb]').forEach((button) => {
                    button.classList.toggle('is-active', button === thumbButton);
                });
            });
        });
    }

    const optionsRoot = document.querySelector('[data-product-options]');
    if (!optionsRoot) {
        return;
    }

    const optionInputs = Array.from(optionsRoot.querySelectorAll('[data-option-input]'));
    const priceNode = optionsRoot.querySelector('.js-variant-price');
    const compareNode = optionsRoot.querySelector('.js-variant-compare');
    const stockNode = optionsRoot.querySelector('.js-variant-stock');
    const savingsNode = optionsRoot.querySelector('.js-variant-saving');
    const quantityInput = optionsRoot.querySelector('.js-variant-quantity');
    const submitButton = optionsRoot.querySelector('.js-variant-submit');
    const promotionActive = optionsRoot.getAttribute('data-promotion-active') === '1';

    const applyVariantState = (input) => {
        if (!input) {
            return;
        }

        const stock = Number.parseInt(input.getAttribute('data-stock') || '0', 10);
        const comparePrice = promotionActive ? (input.getAttribute('data-compare-price') || '') : '';
        const priceCents = Number.parseInt(input.getAttribute('data-price-cents') || '0', 10);
        const comparePriceCents = promotionActive
            ? Number.parseInt(input.getAttribute('data-compare-price-cents') || '0', 10)
            : 0;
        const savingsCents = comparePriceCents > priceCents ? comparePriceCents - priceCents : 0;

        if (priceNode) {
            priceNode.textContent = input.getAttribute('data-price') || '';
        }

        if (compareNode) {
            compareNode.textContent = comparePrice;
            compareNode.classList.toggle('is-hidden', comparePrice === '');
        }

        if (stockNode) {
            stockNode.textContent = `${stock} disponible${stock > 1 ? 's' : ''}`;
        }

        if (savingsNode) {
            if (savingsCents > 0) {
                savingsNode.textContent = `Vous économisez ${(savingsCents / 100).toFixed(2).replace('.', ',')} EUR`;
                savingsNode.classList.remove('is-hidden');
            } else {
                savingsNode.textContent = '';
                savingsNode.classList.add('is-hidden');
            }
        }

        if (quantityInput) {
            quantityInput.max = String(Math.max(stock, 1));

            if (Number.parseInt(quantityInput.value || '1', 10) > stock) {
                quantityInput.value = stock > 0 ? String(stock) : '1';
            }
        }

        if (submitButton) {
            submitButton.disabled = stock <= 0;
            submitButton.textContent = stock > 0 ? 'Ajouter au panier' : 'Indisponible';
        }
    };

    optionInputs.forEach((input) => {
        input.addEventListener('change', () => applyVariantState(input));
    });

    const checkedInput = optionInputs.find((input) => input.checked);
    if (checkedInput) {
        applyVariantState(checkedInput);
    }
});
