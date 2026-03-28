if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGalleryPage);
} else {
    initGalleryPage();
}

function initGalleryPage() {
    initVideoCarousel();
    initPhotoSliders();
}

function initVideoCarousel() {
    const carousel = document.querySelector('.carousel');
    if (!carousel) return;

    const figure = carousel.querySelector('figure');
    const nav = carousel.querySelector('nav');
    if (!figure || !nav) return;

    const numImages = figure.childElementCount;
    if (numImages === 0) return;

    const theta = (2 * Math.PI) / numImages;
    let currImage = 0;

    nav.addEventListener('click', (event) => {
        event.stopPropagation();

        const button = event.target.closest('button');
        if (!button) return;

        if (button.classList.contains('next')) {
            currImage++;
        } else {
            currImage--;
        }

        figure.style.transform = `rotateY(${currImage * -theta}rad)`;
    }, true);
}

function initPhotoSliders() {
    document.querySelectorAll('[data-photo-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-photo-slider-track]');
        const slides = Array.from(slider.querySelectorAll('[data-photo-slide]'));
        const prevButton = slider.querySelector('[data-photo-slider-prev]');
        const nextButton = slider.querySelector('[data-photo-slider-next]');
        const dots = Array.from(slider.querySelectorAll('[data-photo-slider-dot]'));

        if (!track || slides.length === 0) return;

        let currentIndex = 0;

        const setActiveDot = (index) => {
            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === index;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        };

        const goToSlide = (index, behavior = 'smooth') => {
            currentIndex = (index + slides.length) % slides.length;
            const targetSlide = slides[currentIndex];
            const left = targetSlide.offsetLeft - track.offsetLeft;

            track.scrollTo({
                left,
                behavior,
            });

            setActiveDot(currentIndex);
        };

        if (prevButton) {
            prevButton.addEventListener('click', () => {
                goToSlide(currentIndex - 1);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => {
                goToSlide(currentIndex + 1);
            });
        }

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const index = Number(dot.dataset.photoSliderIndex || 0);
                goToSlide(index);
            });
        });

        let scrollFrame = null;
        track.addEventListener('scroll', () => {
            if (scrollFrame) cancelAnimationFrame(scrollFrame);

            scrollFrame = requestAnimationFrame(() => {
                let nearestIndex = 0;
                let nearestDistance = Number.POSITIVE_INFINITY;

                slides.forEach((slide, index) => {
                    const distance = Math.abs(slide.offsetLeft - track.offsetLeft - track.scrollLeft);
                    if (distance < nearestDistance) {
                        nearestDistance = distance;
                        nearestIndex = index;
                    }
                });

                currentIndex = nearestIndex;
                setActiveDot(currentIndex);
            });
        }, { passive: true });

        setActiveDot(0);
    }
}
