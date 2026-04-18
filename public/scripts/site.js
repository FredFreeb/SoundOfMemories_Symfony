document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const mainNav = document.querySelector('[data-main-nav]');

    if (!navToggle || !mainNav) {
        return;
    }

    navToggle.addEventListener('click', () => {
        const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
        navToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        mainNav.classList.toggle('is-open', !isOpen);
        document.body.classList.toggle('nav-open', !isOpen);
    });
});
