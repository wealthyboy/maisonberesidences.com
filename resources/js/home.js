const heroVideo = document.querySelector('.hero-video[data-hls-source]');

if (heroVideo) {
    const playlist = heroVideo.dataset.hlsSource;
    const revealVideo = () => heroVideo.classList.add('is-ready');
    const hideVideo = () => heroVideo.classList.remove('is-ready');

    heroVideo.addEventListener('playing', revealVideo);
    heroVideo.addEventListener('error', hideVideo);

    if (heroVideo.canPlayType('application/vnd.apple.mpegurl')) {
        heroVideo.src = playlist;
    } else {
        import('hls.js').then(({ default: Hls }) => {
            if (!Hls.isSupported()) {
                return;
            }

            const hls = new Hls({
                enableWorker: true,
                lowLatencyMode: false,
            });

            hls.loadSource(playlist);
            hls.attachMedia(heroVideo);
            hls.on(Hls.Events.ERROR, (_event, data) => {
                if (data.fatal) {
                    hideVideo();
                    hls.destroy();
                }
            });

            window.addEventListener('pagehide', () => hls.destroy(), { once: true });
        });
    }
}

const heroImageCarousel = document.querySelector('[data-hero-image-carousel]');

if (heroImageCarousel) {
    const slides = Array.from(heroImageCarousel.querySelectorAll('[data-hero-image-slide]'));
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (slides.length > 1 && !reduceMotion) {
        let activeIndex = 0;
        const interval = window.setInterval(() => {
            slides[activeIndex].classList.remove('is-active');
            activeIndex = (activeIndex + 1) % slides.length;
            slides[activeIndex].classList.add('is-active');
        }, 5500);

        window.addEventListener('pagehide', () => window.clearInterval(interval), { once: true });
    }
}
