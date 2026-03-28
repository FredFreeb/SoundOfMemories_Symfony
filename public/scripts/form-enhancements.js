document.addEventListener('DOMContentLoaded', () => {
    initAvatarPickers();
    initAddressAutocomplete();
    initPhoneFields();
    initConsentToggles();
});

const EUROPEAN_COUNTRY_CODES = new Set([
    'ad', 'al', 'at', 'ax', 'ba', 'be', 'bg', 'by', 'ch', 'cy', 'cz', 'de', 'dk', 'ee', 'es', 'fi',
    'fo', 'fr', 'gb', 'ge', 'gg', 'gi', 'gr', 'hr', 'hu', 'ie', 'im', 'is', 'it', 'je', 'li', 'lt',
    'lu', 'lv', 'mc', 'md', 'me', 'mk', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'rs', 'se', 'si', 'sk',
    'sm', 'ua', 'uk', 'va', 'xk',
]);

function initAvatarPickers() {
    document.querySelectorAll('[data-avatar-picker]').forEach((trigger) => {
        const inputId = trigger.getAttribute('for');
        if (!inputId) {
            return;
        }

        const input = document.getElementById(inputId);
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const preview = trigger.querySelector('[data-avatar-preview]');
        const stack = trigger.closest('.account-avatar-stack');
        const filename = stack?.querySelector('[data-avatar-filename]');

        trigger.addEventListener('click', () => {
            stack?.classList.add('is-active');
        });

        input.addEventListener('change', () => {
            const [file] = input.files || [];

            if (!file) {
                if (filename) {
                    filename.textContent = 'JPEG, PNG ou WebP, 2 Mo maximum.';
                }
                return;
            }

            if (filename) {
                filename.textContent = file.name;
            }

            stack?.classList.add('is-active');

            if (!preview || !file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.addEventListener('load', () => {
                const image = document.createElement('img');
                image.src = String(reader.result || '');
                image.alt = 'Nouvel avatar selectionne';
                preview.innerHTML = '';
                preview.appendChild(image);
                preview.classList.add('has-image');
            });
            reader.readAsDataURL(file);

            window.setTimeout(() => {
                stack?.classList.remove('is-active');
            }, 1800);
        });

        document.addEventListener('click', (event) => {
            if (stack && !stack.contains(event.target)) {
                stack.classList.remove('is-active');
            }
        });
    });
}

function initAddressAutocomplete() {
    document.querySelectorAll('[data-address-autocomplete-form]').forEach((form) => {
        const endpoint = form.getAttribute('data-address-autocomplete-endpoint');
        const cityInput = form.querySelector('[data-address-role="city"]');
        const streetInput = form.querySelector('[data-address-role="street"]');
        const postalInput = form.querySelector('[data-address-role="postal-code"]');

        if (!endpoint || !(cityInput instanceof HTMLInputElement) || !(streetInput instanceof HTMLInputElement) || !(postalInput instanceof HTMLInputElement)) {
            return;
        }

        const cityPanel = createAutocompletePanel(cityInput);
        const postalPanel = createAutocompletePanel(postalInput);
        const streetPanel = createAutocompletePanel(streetInput);
        const postalWrapper = postalInput.parentElement;
        const postalHint = createAddressHint(postalInput);
        let cityAbortController = null;
        let postalAbortController = null;
        let streetAbortController = null;

        const updateStreetAvailability = () => {
            const hasCity = cityInput.value.trim().length >= 2;
            streetInput.disabled = !hasCity;
            streetInput.setAttribute('aria-disabled', hasCity ? 'false' : 'true');

            if (!hasCity) {
                streetInput.value = '';
                streetInput.placeholder = 'Choisissez d abord une ville';
                streetInput.dataset.selectedLat = '';
                streetInput.dataset.selectedLon = '';
                closeAutocompletePanel(streetPanel);
            } else {
                streetInput.placeholder = 'Puis choisissez votre rue';
            }
        };

        const updatePostalHint = () => {
            const hasCity = cityInput.value.trim().length >= 2;
            postalInput.placeholder = hasCity ? 'Code postal de cette ville' : 'Code postal';

            if (postalWrapper) {
                postalWrapper.classList.toggle('is-address-guided-waiting', !hasCity);
                postalWrapper.classList.toggle('is-address-guided-ready', hasCity);
            }

            if (postalHint) {
                postalHint.textContent = hasCity
                    ? `Suggestions liées à ${cityInput.value.trim()}. Vous pouvez aussi saisir le code postal manuellement.`
                    : 'Choisissez d abord la ville pour obtenir des suggestions de code postal plus précises.';
            }
        };

        updateStreetAvailability();
        updatePostalHint();

        const requestCities = debounce(async () => {
            const query = cityInput.value.trim();
            if (query.length < 2) {
                closeAutocompletePanel(cityPanel);
                updateStreetAvailability();
                return;
            }

            if (cityAbortController) {
                cityAbortController.abort();
            }

            cityAbortController = new AbortController();

            try {
                const url = new URL(endpoint);
                url.searchParams.set('q', query);
                url.searchParams.set('limit', '7');
                url.searchParams.set('lang', 'fr');

                const response = await fetch(url, {
                    signal: cityAbortController.signal,
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    closeAutocompletePanel(cityPanel);
                    return;
                }

                const payload = await response.json();
                const suggestions = dedupeSuggestions(
                    (payload.features || [])
                        .map((feature) => normalizeCityFeature(feature))
                        .filter(Boolean)
                        .sort((left, right) => scoreCitySuggestion(right, query) - scoreCitySuggestion(left, query))
                        .slice(0, 6)
                );

                renderAutocompletePanel(cityPanel, suggestions, (item) => {
                    cityInput.value = item.label;
                    cityInput.dataset.selectedLat = item.lat ?? '';
                    cityInput.dataset.selectedLon = item.lon ?? '';
                    cityInput.dataset.selectedCountry = item.countryCode ?? '';
                    if (item.postcode) {
                        postalInput.value = item.postcode;
                    }
                    updateStreetAvailability();
                    updatePostalHint();
                    streetInput.focus();
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    closeAutocompletePanel(cityPanel);
                }
            }
        }, 220);

        const requestPostalCodes = debounce(async () => {
            const postalQuery = postalInput.value.trim();
            const cityQuery = cityInput.value.trim();

            if (postalQuery.length < 2) {
                closeAutocompletePanel(postalPanel);
                return;
            }

            if (postalAbortController) {
                postalAbortController.abort();
            }

            postalAbortController = new AbortController();

            try {
                const url = new URL(endpoint);
                url.searchParams.set('q', cityQuery !== '' ? `${postalQuery} ${cityQuery}` : postalQuery);
                url.searchParams.set('limit', '7');
                url.searchParams.set('lang', 'fr');

                const response = await fetch(url, {
                    signal: postalAbortController.signal,
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    closeAutocompletePanel(postalPanel);
                    return;
                }

                const payload = await response.json();
                const suggestions = dedupeSuggestions(
                    (payload.features || [])
                        .map((feature) => normalizePostalFeature(feature))
                        .filter(Boolean)
                        .slice(0, 6)
                );

                renderAutocompletePanel(postalPanel, suggestions, (item) => {
                    postalInput.value = item.postcode;

                    if (item.city) {
                        cityInput.value = item.city;
                    }

                    if (item.lat && item.lon) {
                        cityInput.dataset.selectedLat = item.lat;
                        cityInput.dataset.selectedLon = item.lon;
                    }

                    updateStreetAvailability();
                    updatePostalHint();
                    streetInput.focus();
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    closeAutocompletePanel(postalPanel);
                }
            }
        }, 220);

        const requestStreets = debounce(async () => {
            const streetQuery = streetInput.value.trim();
            const cityQuery = cityInput.value.trim();

            if (streetQuery.length < 2 || cityQuery.length < 2) {
                closeAutocompletePanel(streetPanel);
                return;
            }

            if (streetAbortController) {
                streetAbortController.abort();
            }

            streetAbortController = new AbortController();

            try {
                const url = new URL(endpoint);
                url.searchParams.set('q', `${streetQuery} ${cityQuery}`.trim());
                url.searchParams.set('limit', '8');
                url.searchParams.set('lang', 'fr');

                const lat = cityInput.dataset.selectedLat;
                const lon = cityInput.dataset.selectedLon;

                if (lat && lon) {
                    url.searchParams.set('lat', lat);
                    url.searchParams.set('lon', lon);
                }

                const response = await fetch(url, {
                    signal: streetAbortController.signal,
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    closeAutocompletePanel(streetPanel);
                    return;
                }

                const payload = await response.json();
                const suggestions = dedupeSuggestions(
                    (payload.features || [])
                        .map((feature) => normalizeStreetFeature(feature, cityQuery))
                        .filter(Boolean)
                        .slice(0, 6)
                );

                renderAutocompletePanel(streetPanel, suggestions, (item) => {
                    streetInput.value = item.label;
                    if (item.postcode) {
                        postalInput.value = item.postcode;
                    }
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    closeAutocompletePanel(streetPanel);
                }
            }
        }, 220);

        cityInput.addEventListener('input', () => {
            cityInput.dataset.selectedLat = '';
            cityInput.dataset.selectedLon = '';
            cityInput.dataset.selectedCountry = '';
            updateStreetAvailability();
            updatePostalHint();
            requestCities();
        });

        postalInput.addEventListener('input', requestPostalCodes);
        streetInput.addEventListener('input', requestStreets);

        [cityInput, postalInput, streetInput].forEach((input) => {
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAutocompletePanel(cityPanel);
                    closeAutocompletePanel(postalPanel);
                    closeAutocompletePanel(streetPanel);
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!cityPanel.contains(event.target) && event.target !== cityInput) {
                closeAutocompletePanel(cityPanel);
            }

            if (!postalPanel.contains(event.target) && event.target !== postalInput) {
                closeAutocompletePanel(postalPanel);
            }

            if (!streetPanel.contains(event.target) && event.target !== streetInput) {
                closeAutocompletePanel(streetPanel);
            }
        });
    });
}

function initPhoneFields() {
    document.querySelectorAll('[data-phone-grid]').forEach((wrapper) => {
        const countrySelect = wrapper.querySelector('[data-phone-country]');
        const phoneInput = wrapper.querySelector('[data-phone-local]');
        const endpoint = wrapper.getAttribute('data-phone-guess-endpoint');

        if (!(countrySelect instanceof HTMLSelectElement) || !(phoneInput instanceof HTMLInputElement)) {
            return;
        }

        const assist = createPhoneAssist(wrapper);
        let guessAbortController = null;

        const applyRegion = (region, message = '') => {
            if (!region || countrySelect.value === region) {
                return;
            }

            countrySelect.value = region;
            countrySelect.dispatchEvent(new Event('change', { bubbles: true }));

            if (assist && message !== '') {
                assist.textContent = message;
                assist.hidden = false;
                wrapper.classList.add('is-phone-auto-adjusted');

                window.clearTimeout(Number(wrapper.dataset.phoneAssistTimer || 0));
                const timer = window.setTimeout(() => {
                    assist.hidden = true;
                    wrapper.classList.remove('is-phone-auto-adjusted');
                }, 2600);
                wrapper.dataset.phoneAssistTimer = String(timer);
            }
        };

        const showAssist = (message) => {
            if (!assist || message === '') {
                return;
            }

            assist.textContent = message;
            assist.hidden = false;
            window.clearTimeout(Number(wrapper.dataset.phoneAssistTimer || 0));
            const timer = window.setTimeout(() => {
                assist.hidden = true;
                wrapper.classList.remove('is-phone-auto-adjusted');
            }, 3200);
            wrapper.dataset.phoneAssistTimer = String(timer);
        };

        const syncCountryFromNumber = () => {
            const detectedRegion = detectRegionFromPhoneInput(phoneInput.value, countrySelect);
            if (detectedRegion) {
                const option = countrySelect.querySelector(`option[value="${detectedRegion}"]`);
                const label = option ? option.textContent?.trim() || '' : '';
                applyRegion(detectedRegion, label !== '' ? `Indicatif ajusté automatiquement : ${label}` : '');
            }
        };

        const syncCountryWithServerGuess = debounce(async () => {
            const value = phoneInput.value.trim();

            if (!endpoint || value.length < 6) {
                return;
            }

            if (guessAbortController) {
                guessAbortController.abort();
            }

            guessAbortController = new AbortController();

            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('value', value);
                url.searchParams.set('preferred', countrySelect.value || 'FR');

                const response = await fetch(url, {
                    signal: guessAbortController.signal,
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                if (payload.region) {
                    applyRegion(String(payload.region), payload.label ? `Indicatif ajusté automatiquement : ${payload.label}` : '');
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    return;
                }
            }
        }, 260);

        const syncAll = () => {
            syncCountryFromNumber();
            syncCountryWithServerGuess();
        };

        phoneInput.addEventListener('input', syncAll);
        phoneInput.addEventListener('blur', syncAll);
    });
}

function initConsentToggles() {
    document.querySelectorAll('[data-consent-toggle]').forEach((checkbox) => {
        if (!(checkbox instanceof HTMLInputElement)) {
            return;
        }

        const container = checkbox.closest('.account-consent-field');
        const pill = container?.querySelector('[data-consent-state-pill]');
        if (!(pill instanceof HTMLElement)) {
            return;
        }

        const syncConsentState = () => {
            const checked = checkbox.checked;
            pill.textContent = checked ? 'Consentement actif' : 'Consentement inactif';
            pill.classList.toggle('account-status-pill-muted', !checked);
        };

        syncConsentState();
        checkbox.addEventListener('change', syncConsentState);
    });
}

function createAutocompletePanel(input) {
    const wrapper = input.parentElement;
    if (!wrapper) {
        return document.createElement('div');
    }

    wrapper.classList.add('address-autocomplete-wrapper');

    const panel = document.createElement('div');
    panel.className = 'address-autocomplete-panel';
    panel.hidden = true;
    wrapper.appendChild(panel);

    return panel;
}

function createAddressHint(input) {
    const wrapper = input.parentElement;
    if (!wrapper) {
        return null;
    }

    wrapper.classList.add('address-guided-field');

    const existing = wrapper.querySelector('[data-address-hint]');
    if (existing instanceof HTMLElement) {
        return existing;
    }

    const hint = document.createElement('p');
    hint.className = 'address-field-hint';
    hint.setAttribute('data-address-hint', 'true');
    wrapper.appendChild(hint);

    return hint;
}

function createPhoneAssist(wrapper) {
    const existing = wrapper.parentElement?.querySelector('[data-phone-assist]');
    if (existing instanceof HTMLElement) {
        return existing;
    }

    const assist = document.createElement('p');
    assist.className = 'account-phone-assist';
    assist.setAttribute('data-phone-assist', 'true');
    assist.hidden = true;
    wrapper.insertAdjacentElement('afterend', assist);

    return assist;
}

function renderAutocompletePanel(panel, suggestions, onSelect) {
    panel.innerHTML = '';

    if (!suggestions.length) {
        panel.hidden = true;
        return;
    }

    suggestions.forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'address-autocomplete-option';
        button.innerHTML = `
            <strong>${escapeHtml(item.label)}</strong>
            ${item.secondary ? `<span>${escapeHtml(item.secondary)}</span>` : ''}
        `;
        button.addEventListener('click', () => {
            onSelect(item);
            panel.hidden = true;
        });
        panel.appendChild(button);
    });

    panel.hidden = false;
}

function closeAutocompletePanel(panel) {
    panel.hidden = true;
    panel.innerHTML = '';
}

function normalizeCityFeature(feature) {
    const properties = feature?.properties || {};
    const coordinates = feature?.geometry?.coordinates || [];
    const city = properties.city || properties.town || properties.village || properties.name || '';
    const country = properties.country || '';
    const countryCode = String(properties.countrycode || '').toLowerCase();
    const postcode = properties.postcode || '';

    if (!city) {
        return null;
    }

    const secondary = [postcode, country].filter(Boolean).join(' · ');

    return {
        label: city,
        secondary,
        countryCode,
        postcode,
        lat: coordinates[1] ?? '',
        lon: coordinates[0] ?? '',
    };
}

function normalizeStreetFeature(feature, fallbackCity) {
    const properties = feature?.properties || {};
    const street = properties.street || properties.name || '';
    const houseNumber = properties.housenumber || '';
    const city = properties.city || properties.town || properties.village || fallbackCity || '';
    const postcode = properties.postcode || '';
    const country = properties.country || '';
    const label = [street, houseNumber].filter(Boolean).join(' ').trim();

    if (!label) {
        return null;
    }

    return {
        label,
        postcode,
        secondary: [postcode, city, country].filter(Boolean).join(' · '),
    };
}

function normalizePostalFeature(feature) {
    const properties = feature?.properties || {};
    const coordinates = feature?.geometry?.coordinates || [];
    const postcode = properties.postcode || '';
    const city = properties.city || properties.town || properties.village || properties.name || '';
    const country = properties.country || '';

    if (!postcode) {
        return null;
    }

    return {
        label: postcode,
        postcode,
        city,
        lat: coordinates[1] ?? '',
        lon: coordinates[0] ?? '',
        secondary: [city, country].filter(Boolean).join(' · '),
    };
}

function scoreCitySuggestion(item, query) {
    const normalizedQuery = query.trim().toLowerCase();
    const normalizedLabel = item.label.toLowerCase();
    let score = 0;

    if (normalizedLabel === normalizedQuery) {
        score += 8;
    } else if (normalizedLabel.startsWith(normalizedQuery)) {
        score += 5;
    } else if (normalizedLabel.includes(normalizedQuery)) {
        score += 2;
    }

    if (EUROPEAN_COUNTRY_CODES.has(item.countryCode)) {
        score += 3;
    }

    if (item.postcode) {
        score += 1;
    }

    return score;
}

function dedupeSuggestions(items) {
    const seen = new Set();

    return items.filter((item) => {
        const key = `${item.label}|${item.secondary || ''}`;
        if (seen.has(key)) {
            return false;
        }
        seen.add(key);
        return true;
    });
}

function detectRegionFromPhoneInput(rawValue, countrySelect) {
    const normalizedValue = rawValue.trim().replace(/\s+/g, '');
    if (!normalizedValue.startsWith('+') && !normalizedValue.startsWith('00')) {
        return null;
    }

    const internationalValue = normalizedValue.startsWith('00')
        ? `+${normalizedValue.slice(2)}`
        : normalizedValue;
    const digits = internationalValue.replace(/^\+/, '').replace(/\D/g, '');

    if (digits.length < 2) {
        return null;
    }

    const options = Array.from(countrySelect.options)
        .map((option) => ({
            value: option.value,
            dialCode: String(option.dataset.dialCode || ''),
        }))
        .filter((option) => option.value !== '' && option.dialCode !== '')
        .sort((left, right) => right.dialCode.length - left.dialCode.length);

    const match = options.find((option) => digits.startsWith(option.dialCode));

    return match ? match.value : null;
}

function debounce(callback, delay) {
    let timeoutId = null;

    return (...args) => {
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => callback(...args), delay);
    };
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
