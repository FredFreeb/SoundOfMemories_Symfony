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
    const quantityInput = optionsRoot.querySelector('.js-variant-quantity');
    const submitButton = optionsRoot.querySelector('.js-variant-submit');

    const applyVariantState = (input) => {
        if (!input) {
            return;
        }

        const stock = Number.parseInt(input.getAttribute('data-stock') || '0', 10);
        const comparePrice = input.getAttribute('data-compare-price') || '';

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
