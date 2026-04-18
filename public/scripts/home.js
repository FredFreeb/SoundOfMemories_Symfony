document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-scroll-target]').forEach((container) => {
        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;

        container.addEventListener('pointerdown', (event) => {
            isDown = true;
            startX = event.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
            container.classList.add('is-dragging');
        });

        container.addEventListener('pointerleave', () => {
            isDown = false;
            container.classList.remove('is-dragging');
        });

        container.addEventListener('pointerup', () => {
            isDown = false;
            container.classList.remove('is-dragging');
        });

        container.addEventListener('pointermove', (event) => {
            if (!isDown) {
                return;
            }

            event.preventDefault();
            const x = event.pageX - container.offsetLeft;
            const walk = (x - startX) * 1.2;
            container.scrollLeft = scrollLeft - walk;
        });
    });

    initHeroRotator();
    initHeroVideo();
    initScrollIndicator();
});

function initHeroRotator() {
    const hero = document.querySelector('[data-hero-rotator]');
    if (!hero) return;

    const currentLayer = hero.querySelector('[data-hero-bg-current]');
    const nextLayer = hero.querySelector('[data-hero-bg-next]');
    if (!currentLayer || !nextLayer) return;

    const images = safeParseImages(hero.dataset.heroImages);
    if (images.length <= 1) {
        currentLayer.style.opacity = '1';
        return;
    }

    const gradient = 'linear-gradient(180deg, rgba(8, 8, 8, 0.42), rgba(8, 8, 8, 0.88))';
    let index = 0;

    const setBackground = (el, url) => {
        el.style.backgroundImage = `${gradient}, url('${url}')`;
    };

    setBackground(currentLayer, images[index]);
    setBackground(nextLayer, images[(index + 1) % images.length]);

    setInterval(() => {
        const nextIndex = (index + 1) % images.length;
        setBackground(nextLayer, images[nextIndex]);

        nextLayer.classList.add('is-visible');
        currentLayer.classList.remove('is-visible');

        setTimeout(() => {
            // swap layers
            setBackground(currentLayer, images[nextIndex]);
            currentLayer.classList.add('is-visible');
            nextLayer.classList.remove('is-visible');
            index = nextIndex;
        }, 1100);
    }, 6500);
}

function safeParseImages(jsonString) {
    try {
        const parsed = JSON.parse(jsonString || '[]');
        return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
    } catch {
        return [];
    }
}

function initScrollIndicator() {
    const indicator = document.querySelector('[data-scroll-indicator]');
    const hero = document.querySelector('[data-home-hero], [data-hero-rotator]');
    if (!indicator || !hero) return;

    const target = hero.nextElementSibling;
    if (!target) return;

    indicator.addEventListener('click', () => {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function initHeroVideo() {
    const video = document.querySelector('[data-home-hero-video]');
    if (!video) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        video.pause();
        return;
    }

    const playVideo = () => video.play().catch(() => {});
    playVideo();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                playVideo();
            } else {
                video.pause();
            }
        });
    }, { threshold: 0.2 });

    observer.observe(video);
}
