/**
 * Sound Of Memories — Dynamic Visual Effects
 * Scroll reveals, parallax, particle grain, and interactive glow effects.
 */
document.addEventListener('DOMContentLoaded', () => {
    initScrollReveal();
    initParallax();
    initFilmGrain();
    initGlowCursor();
    initSectionCountUp();
});

/* ── Scroll Reveal ───────────────────────────────────────────────── */
function initScrollReveal() {
    const targets = document.querySelectorAll(
        '.product-card, .concert-card, .press-card, .section-head, ' +
        '.masked-image-section__copy, .masked-image-section__media, ' +
        '.hero-copy, .hero-visual-shell, .band-member-card, .band-release-card, ' +
        '.editorial-member-card, .editorial-release-card, .editorial-concert-beat, ' +
        '.shop-storefront-header, .album-player-card, .photo-slide, ' +
        '.faq-item, .login-showcase, .login-card'
    );

    if (!targets.length) return;

    targets.forEach(el => el.classList.add('reveal'));

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal--visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    targets.forEach(el => observer.observe(el));
}

/* ── Parallax on section backgrounds ─────────────────────────────── */
function initParallax() {
    const parallaxSections = document.querySelectorAll('.section, .page-banner, .hero-section');

    if (!parallaxSections.length || prefersReducedMotion()) return;

    const updateParallax = () => {
        parallaxSections.forEach(section => {
            const rect = section.getBoundingClientRect();
            const sectionCenter = rect.top + rect.height / 2;
            const viewCenter = window.innerHeight / 2;
            const factor = Number.parseFloat(section.dataset.parallaxFactor || '0.04');
            const offset = (sectionCenter - viewCenter) * factor;
            section.style.setProperty('--parallax-y', `${offset}px`);
        });
    };

    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                updateParallax();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    window.addEventListener('resize', updateParallax, { passive: true });
    updateParallax();
}

/* ── Film Grain Overlay ──────────────────────────────────────────── */
function initFilmGrain() {
    if (prefersReducedMotion()) return;

    const canvas = document.createElement('canvas');
    canvas.className = 'film-grain';
    canvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    let w, h;

    function resize() {
        w = canvas.width = window.innerWidth;
        h = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    function drawGrain() {
        const imageData = ctx.createImageData(w, h);
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            const v = Math.random() * 255;
            data[i] = v;
            data[i + 1] = v;
            data[i + 2] = v;
            data[i + 3] = 12; // very subtle
        }
        ctx.putImageData(imageData, 0, 0);
        requestAnimationFrame(drawGrain);
    }
    drawGrain();
}

/* ── Interactive Glow on Hero ────────────────────────────────────── */
function initGlowCursor() {
    const hero = document.querySelector('.hero-section');
    if (!hero || prefersReducedMotion()) return;

    const glow = document.createElement('div');
    glow.className = 'cursor-glow';
    glow.setAttribute('aria-hidden', 'true');
    hero.appendChild(glow);

    hero.addEventListener('pointermove', (e) => {
        const rect = hero.getBoundingClientRect();
        glow.style.setProperty('--glow-x', `${e.clientX - rect.left}px`);
        glow.style.setProperty('--glow-y', `${e.clientY - rect.top}px`);
        glow.style.opacity = '1';
    });

    hero.addEventListener('pointerleave', () => {
        glow.style.opacity = '0';
    });
}

/* ── Count-up animation for stats ────────────────────────────────── */
function initSectionCountUp() {
    const counters = document.querySelectorAll('[data-count-target]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.countTarget, 10);
                if (isNaN(target)) return;
                animateCount(el, 0, target, 1200);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
}

function animateCount(el, start, end, duration) {
    const range = end - start;
    const startTime = performance.now();

    function step(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(start + range * eased);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

/* ── Utility ──────────────────────────────────────────────────────── */
function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}
