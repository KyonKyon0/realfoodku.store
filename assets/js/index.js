document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');

    if (!searchInput || !searchBtn) {
        return;
    }

    searchInput.addEventListener('input', (event) => {
        searchBtn.style.opacity = event.target.value.trim().length > 0 ? '1' : '0.85';
    });
});
