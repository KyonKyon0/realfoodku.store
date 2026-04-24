document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');

    if (searchInput && searchBtn) {
        searchInput.addEventListener('input', (event) => {
            searchBtn.style.opacity = event.target.value.trim().length > 0 ? '1' : '0.85';
        });
    }

    const carousels = document.querySelectorAll('[data-carousel]');
    carousels.forEach((carousel) => {
        const key = carousel.getAttribute('data-carousel');
        const dotsWrap = document.querySelector(`[data-dots-for="${key}"]`);
        if (!dotsWrap) return;

        const cards = Array.from(carousel.children);
        dotsWrap.innerHTML = '';

        cards.forEach((_, index) => {
            const dot = document.createElement('button');
            if (index === 0) dot.classList.add('active');
            dot.setAttribute('aria-label', `Slide ${index + 1}`);
            dot.addEventListener('click', () => {
                const card = cards[index];
                carousel.scrollTo({ left: card.offsetLeft - carousel.offsetLeft, behavior: 'smooth' });
            });
            dotsWrap.appendChild(dot);
        });

        const updateActiveDot = () => {
            const centerX = carousel.scrollLeft + (carousel.clientWidth / 2);
            let activeIndex = 0;
            let minDistance = Infinity;

            cards.forEach((card, index) => {
                const cardCenter = card.offsetLeft + (card.clientWidth / 2);
                const distance = Math.abs(cardCenter - centerX);
                if (distance < minDistance) {
                    minDistance = distance;
                    activeIndex = index;
                }
            });

            Array.from(dotsWrap.children).forEach((dot, idx) => {
                dot.classList.toggle('active', idx === activeIndex);
            });
        };

        carousel.addEventListener('scroll', updateActiveDot, { passive: true });
        window.addEventListener('resize', updateActiveDot);
        updateActiveDot();
    });
});
