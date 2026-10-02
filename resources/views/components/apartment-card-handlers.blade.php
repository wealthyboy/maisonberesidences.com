<script>
        (() => {
            if (window.apartmentCardHandlersReady) return;
            window.apartmentCardHandlersReady = true;

            const openModal = (modal) => {
                if (!modal) return;
                modal.showModal();
                requestAnimationFrame(() => modal.classList.add('is-open'));
            };

            const closeModal = (modal) => {
                if (!modal || !modal.open || modal.classList.contains('is-closing')) return;
                modal.classList.remove('is-open');
                modal.classList.add('is-closing');

                const finish = () => {
                    modal.classList.remove('is-closing');
                    modal.close();
                };

                const timeout = window.setTimeout(finish, 460);
                modal.addEventListener('transitionend', (transitionEvent) => {
                    if (transitionEvent.target !== modal) return;
                    window.clearTimeout(timeout);
                    finish();
                }, { once: true });
            };

            document.addEventListener('click', (event) => {
                const clickedRefundPreview = event.target.closest('.refund-policy-preview');
                document.querySelectorAll('.refund-policy-preview[open]').forEach((preview) => {
                    if (preview !== clickedRefundPreview) preview.removeAttribute('open');
                });

                const modalSliderButton = event.target.closest('[data-modal-previous], [data-modal-next]');
                if (modalSliderButton) {
                    event.preventDefault();
                    const slider = modalSliderButton.closest('[data-modal-slider]');
                    const slides = [...slider.querySelectorAll('[data-modal-slide]')];
                    const progress = [...slider.querySelectorAll('[data-modal-progress]')];
                    const count = slider.querySelector('[data-modal-count]');
                    let active = slides.findIndex((slide) => slide.classList.contains('is-active'));
                    active = (active + (modalSliderButton.matches('[data-modal-next]') ? 1 : -1) + slides.length) % slides.length;
                    slides.forEach((slide, index) => slide.classList.toggle('is-active', index === active));
                    progress.forEach((item, index) => item.classList.toggle('is-active', index === active));
                    if (count) count.textContent = `${active + 1} / ${slides.length}`;
                }

                const galleryButton = event.target.closest('[data-card-previous], [data-card-next]');
                if (galleryButton) {
                    event.preventDefault();
                    const gallery = galleryButton.closest('[data-card-gallery]');
                    const slides = [...gallery.querySelectorAll('[data-card-slide]')];
                    const progress = [...gallery.querySelectorAll('[data-card-progress]')];
                    const caption = gallery.querySelector('[data-card-caption]');
                    let active = slides.findIndex((slide) => slide.classList.contains('is-active'));
                    active = (active + (galleryButton.matches('[data-card-next]') ? 1 : -1) + slides.length) % slides.length;
                    slides.forEach((slide, index) => slide.classList.toggle('is-active', index === active));
                    progress.forEach((item, index) => item.classList.toggle('is-active', index === active));
                    if (caption) {
                        caption.textContent = slides[active].dataset.caption ?? '';
                        caption.hidden = caption.textContent.trim() === '';
                    }
                }

                const open = event.target.closest('[data-card-modal-open]');
                if (open) {
                    const modal = document.getElementById(open.getAttribute('aria-controls'));
                    const card = open.closest('[data-apartment-card]');
                    const cardSlides = [...card.querySelectorAll('[data-card-slide]')];
                    const activeIndex = Math.max(0, cardSlides.findIndex((slide) => slide.classList.contains('is-active')));
                    const modalSlides = [...modal.querySelectorAll('[data-modal-slide]')];
                    const modalProgress = [...modal.querySelectorAll('[data-modal-progress]')];
                    const modalCount = modal.querySelector('[data-modal-count]');
                    modalSlides.forEach((slide, index) => slide.classList.toggle('is-active', index === activeIndex));
                    modalProgress.forEach((item, index) => item.classList.toggle('is-active', index === activeIndex));
                    if (modalCount) modalCount.textContent = `${activeIndex + 1} / ${modalSlides.length}`;
                    openModal(modal);
                }

                const close = event.target.closest('[data-card-modal-close]');
                if (close) closeModal(close.closest('[data-card-modal]'));

                if (event.target.matches('[data-card-modal]')) {
                    const bounds = event.target.getBoundingClientRect();
                    const clickedInside = event.clientX >= bounds.left && event.clientX <= bounds.right && event.clientY >= bounds.top && event.clientY <= bounds.bottom;
                    if (!clickedInside) closeModal(event.target);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                document.querySelectorAll('.refund-policy-preview[open]').forEach((preview) => preview.removeAttribute('open'));
            });

            document.addEventListener('cancel', (event) => {
                const modal = event.target.closest('[data-card-modal]');
                if (!modal) return;
                event.preventDefault();
                closeModal(modal);
            });

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('[data-availability-form]');
                if (!form) return;
                if (event.defaultPrevented) return;
                event.preventDefault();
                const status = form.parentElement.querySelector('[data-availability-status]');
                const bookNow = form.parentElement.querySelector('[data-book-now]');
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton?.dataset.available === 'true' && submitButton.dataset.reserveUrl) {
                    window.location.href = submitButton.dataset.reserveUrl;
                    return;
                }

                status.textContent = '';
                status.classList.remove('is-success', 'is-error');
                bookNow.hidden = true;
                bookNow.href = '#';
                form.setAttribute('aria-busy', 'true');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Checking availability...';
                    submitButton.dataset.available = 'false';
                    submitButton.dataset.reserveUrl = '';
                }
                try {
                    const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: new FormData(form) });
                    const result = await response.json();
                    status.textContent = result.message || 'We could not check availability.';
                    if (result.available && result.reserve_url) {
                        status.classList.add('is-success');
                        bookNow.href = result.reserve_url;
                        if (submitButton) {
                            submitButton.textContent = 'Book now →';
                            submitButton.dataset.available = 'true';
                            submitButton.dataset.reserveUrl = result.reserve_url;
                        }
                    } else {
                        status.classList.add('is-error');
                        if (submitButton) submitButton.textContent = 'Check availability';
                    }
                } catch {
                    status.textContent = 'We could not check availability. Please try again.';
                    status.classList.add('is-error');
                    bookNow.hidden = true;
                    bookNow.href = '#';
                    if (submitButton) submitButton.textContent = 'Check availability';
                } finally {
                    form.removeAttribute('aria-busy');
                    if (submitButton) submitButton.disabled = false;
                }
            });
        })();
    </script>
