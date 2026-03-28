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
});
