const heroVideo = document.querySelector('.hero-video[data-hls-source]');

if (heroVideo) {
    const playlist = heroVideo.dataset.hlsSource;
    const revealVideo = () => heroVideo.classList.add('is-ready');
    const hideVideo = () => heroVideo.classList.remove('is-ready');
    const startPlayback = () => {
        heroVideo.play().catch(() => hideVideo());
    };

    heroVideo.addEventListener('playing', revealVideo);
    heroVideo.addEventListener('error', hideVideo);

    if (heroVideo.canPlayType('application/vnd.apple.mpegurl')) {
        heroVideo.src = playlist;
        heroVideo.load();
        startPlayback();
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
            hls.on(Hls.Events.MANIFEST_PARSED, startPlayback);
            hls.on(Hls.Events.ERROR, (_event, data) => {
                if (!data.fatal) {
                    return;
                }

                if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                    hls.startLoad();
                    return;
                }

                if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                    hls.recoverMediaError();
                    return;
                }

                hideVideo();
                hls.destroy();
            });

            window.addEventListener('pagehide', () => hls.destroy(), { once: true });
        }).catch(hideVideo);
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

document.querySelectorAll('[data-residences-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-residences-track]');
    const slides = Array.from(carousel.querySelectorAll('[data-residences-slide]'));
    const previous = carousel.querySelector('[data-residences-previous]');
    const next = carousel.querySelector('[data-residences-next]');

    if (!track || !previous || !next || slides.length === 0) {
        return;
    }

    const scrollAmount = () => {
        const styles = window.getComputedStyle(track);
        const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 0;

        return slides[0].getBoundingClientRect().width + gap;
    };

    const updateControls = () => {
        const maximum = Math.max(0, track.scrollWidth - track.clientWidth);
        const isStatic = maximum < 2;

        carousel.classList.toggle('is-static', isStatic);
        previous.disabled = isStatic || track.scrollLeft <= 2;
        next.disabled = isStatic || track.scrollLeft >= maximum - 2;
    };

    previous.addEventListener('click', () => {
        track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
    });

    next.addEventListener('click', () => {
        track.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
    });

    let frame;
    track.addEventListener('scroll', () => {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(updateControls);
    }, { passive: true });
    window.addEventListener('resize', updateControls, { passive: true });
    updateControls();
});
