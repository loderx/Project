(function () {
    const stored = localStorage.getItem('theme');
    if (stored) {
        document.body.classList.toggle('theme-dark', stored === 'dark');
    }

    const toggle = document.querySelector('[data-theme-toggle]');
    if (toggle) {
        toggle.addEventListener('click', () => {
            document.body.classList.toggle('theme-dark');
            const isDark = document.body.classList.contains('theme-dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    }

    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    if (isMobile) {
        document.body.classList.add('is-mobile');
    }
})();
