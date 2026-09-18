const heroVideo = document.querySelector('.hero-video[data-hls-source]');

if (heroVideo) {
    const playlist = heroVideo.dataset.hlsSource;

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

            window.addEventListener('pagehide', () => hls.destroy(), { once: true });
        });
    }
}
