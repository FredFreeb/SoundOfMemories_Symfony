if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGalleryPage);
} else {
    initGalleryPage();
}

let spotifyIframeApiPromise = null;

function initGalleryPage() {
    initVideoCarousel();
    initAlbumPlayer();
    initPhotoSliders();
}

function findClosestButton(target) {
    while (target && target.tagName !== 'BUTTON') {
        target = target.parentElement;
    }

    return target;
}

function initVideoCarousel() {
    const carousel = document.querySelector('.carousel');
    if (!carousel) return;

    const figure = carousel.querySelector('figure');
    const nav = carousel.querySelector('nav');
    if (!figure || !nav) return;

    const slides = Array.from(figure.querySelectorAll('[data-video-card]'));
    const numImages = slides.length;
    if (!numImages) return;

    const theta = (2 * Math.PI) / numImages;
    let currImage = 0;

    const toActiveIndex = (index) => ((index % numImages) + numImages) % numImages;
    const buildAutoplayUrl = (src) => src + (src.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1&rel=0';

    const hydrateVideo = (slide) => {
        if (!slide || slide.getAttribute('data-video-loaded') === 'true') {
            return;
        }

        const src = slide.getAttribute('data-video-src');
        const title = slide.getAttribute('data-video-title') || 'Video';
        if (!src) return;

        const iframe = document.createElement('iframe');
        iframe.className = 'carousel-card-media';
        iframe.src = buildAutoplayUrl(src);
        iframe.title = title;
        iframe.loading = 'lazy';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        iframe.allowFullscreen = true;
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';

        const preview = slide.querySelector('[data-video-activate]');
        if (preview) {
            preview.replaceWith(iframe);
        } else {
            slide.insertBefore(iframe, slide.firstChild);
        }

        slide.setAttribute('data-video-loaded', 'true');
    };

    const updateActiveSlide = () => {
        const activeIndex = toActiveIndex(currImage);
        slides.forEach((slide, index) => {
            slide.classList.toggle('is-active', index === activeIndex);
        });
    };

    nav.addEventListener('click', (event) => {
        event.stopPropagation();

        const button = findClosestButton(event.target);
        if (!button) return;

        if (button.classList.contains('next')) {
            currImage++;
        } else {
            currImage--;
        }

        figure.style.transform = `rotateY(${currImage * -theta}rad)`;
        updateActiveSlide();
    }, true);

    figure.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-video-activate]');
        if (!trigger) return;

        hydrateVideo(trigger.closest('[data-video-card]'));
    });

    updateActiveSlide();
}

function initPhotoSliders() {
    document.querySelectorAll('[data-photo-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-photo-slider-track]');
        const slides = Array.from(slider.querySelectorAll('[data-photo-slide]'));
        const prevButton = slider.querySelector('[data-photo-slider-prev]');
        const nextButton = slider.querySelector('[data-photo-slider-next]');
        const dots = Array.from(slider.querySelectorAll('[data-photo-slider-dot]'));

        if (!track || slides.length === 0) return;

        let currentIndex = 0;

        const setActiveDot = (index) => {
            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === index;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        };

        const goToSlide = (index, smooth = true) => {
            let targetIndex = index;

            if (targetIndex < 0) {
                targetIndex = slides.length - 1;
            } else if (targetIndex >= slides.length) {
                targetIndex = 0;
            }

            currentIndex = targetIndex;

            const targetSlide = slides[currentIndex];
            const left = targetSlide.offsetLeft - track.offsetLeft;

            if (typeof track.scrollTo === 'function') {
                track.scrollTo({
                    left,
                    behavior: smooth ? 'smooth' : 'auto',
                });
            } else {
                track.scrollLeft = left;
            }

            setActiveDot(currentIndex);
        };

        if (prevButton) {
            prevButton.addEventListener('click', () => {
                goToSlide(currentIndex - 1);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => {
                goToSlide(currentIndex + 1);
            });
        }

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const index = parseInt(dot.getAttribute('data-photo-slider-index') || '0', 10);
                goToSlide(index);
            });
        });

        let scrollFrame = null;
        track.addEventListener('scroll', () => {
            if (scrollFrame) {
                cancelAnimationFrame(scrollFrame);
            }

            scrollFrame = requestAnimationFrame(() => {
                let nearestIndex = 0;
                let nearestDistance = Number.POSITIVE_INFINITY;

                slides.forEach((slide, index) => {
                    const distance = Math.abs((slide.offsetLeft - track.offsetLeft) - track.scrollLeft);
                    if (distance < nearestDistance) {
                        nearestDistance = distance;
                        nearestIndex = index;
                    }
                });

                currentIndex = nearestIndex;
                setActiveDot(currentIndex);
            });
        }, { passive: true });

        setActiveDot(0);
        goToSlide(0, false);

        initPhotoLightbox(slider, slides);
    });
}

function initAlbumPlayer() {
    const player = document.querySelector('[data-album-player]');
    if (!player) return;

    const triggers = Array.from(player.querySelectorAll('[data-album-trigger]'));
    const card = player.querySelector('.album-player-card');
    const visual = player.querySelector('.js-album-visual');
    const cover = player.querySelector('.js-album-cover');
    const title = player.querySelector('.js-album-title');
    const type = player.querySelector('.js-album-type');
    const copy = player.querySelector('.js-album-copy');
    const embedHost = player.querySelector('[data-album-spotify-embed]');
    const toggle = player.querySelector('.js-album-toggle');
    const toggleIcon = player.querySelector('.album-player-control__icon');
    const toggleLabel = player.querySelector('.album-player-control__label');
    const status = player.querySelector('.js-album-status');
    const spotifyLink = player.querySelector('.js-album-link-spotify');
    const appleLink = player.querySelector('.js-album-link-apple');
    const youtubeLink = player.querySelector('.js-album-link-youtube');
    const soundcloudLink = player.querySelector('.js-album-link-soundcloud');

    if (!triggers.length || !cover || !title || !type || !copy || !embedHost) {
        return;
    }

    let embedController = null;
    let currentAlbum = null;
    let hasIframeFallback = false;

    const setPlaybackState = (isPlaying) => {
        if (card) {
            card.classList.toggle('is-playing', isPlaying);
        }

        if (toggle) {
            toggle.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
        }

        if (toggleIcon) {
            toggleIcon.textContent = isPlaying ? '❚❚' : '►';
        }

        if (toggleLabel) {
            toggleLabel.textContent = isPlaying ? 'Pause' : 'Play';
        }

        if (status) {
            status.textContent = isPlaying ? 'Lecture en cours' : 'En pause';
        }
    };

    const setPlayerReadyState = (ready, message) => {
        if (toggle) {
            toggle.disabled = !ready;
        }

        if (status && message) {
            status.textContent = message;
        }
    };

    const getAlbumFromTrigger = (trigger) => ({
        cover: trigger.getAttribute('data-cover') || '',
        title: trigger.getAttribute('data-title') || 'Album',
        type: trigger.getAttribute('data-type') || '',
        copy: trigger.getAttribute('data-copy') || '',
        embed: trigger.getAttribute('data-embed') || '',
        spotifyUri: trigger.getAttribute('data-spotify-uri') || '',
        spotify: trigger.getAttribute('data-spotify') || '#',
        apple: trigger.getAttribute('data-apple') || '#',
        youtube: trigger.getAttribute('data-youtube') || '#',
        soundcloud: trigger.getAttribute('data-soundcloud') || '#',
    });

    const applyAlbum = (album, trigger) => {
        triggers.forEach((button) => {
            const isActive = button === trigger;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        cover.setAttribute('src', album.cover);
        cover.setAttribute('alt', album.title);
        title.textContent = album.title;
        type.textContent = album.type;
        copy.textContent = album.copy;

        if (visual) {
            visual.style.setProperty('--album-cover-url', `url("${album.cover.replace(/"/g, '\\"')}")`);
        }

        if (spotifyLink) {
            spotifyLink.setAttribute('href', album.spotify);
        }
        if (appleLink) {
            appleLink.setAttribute('href', album.apple);
        }
        if (youtubeLink) {
            youtubeLink.setAttribute('href', album.youtube);
        }
        if (soundcloudLink) {
            soundcloudLink.setAttribute('href', album.soundcloud);
        }
    };

    const mountIframeFallback = (album) => {
        if (!album.embed) {
            return;
        }

        embedHost.innerHTML = '';
        const iframe = document.createElement('iframe');
        iframe.src = album.embed;
        iframe.title = `Lecteur Spotify ${album.title}`;
        iframe.width = '100%';
        iframe.height = '352';
        iframe.allowFullscreen = true;
        iframe.allow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.style.borderRadius = '18px';
        iframe.style.border = '0';
        embedHost.appendChild(iframe);
        hasIframeFallback = true;

        if (toggle) {
            toggle.disabled = true;
        }

        if (status) {
            status.textContent = 'Contrôle direct indisponible. Lecture via l’embed Spotify.';
        }
    };

    const ensureSpotifyIframeApi = () => {
        if (window.SpotifyIframeApi && typeof window.SpotifyIframeApi.createController === 'function') {
            return Promise.resolve(window.SpotifyIframeApi);
        }

        if (spotifyIframeApiPromise) {
            return spotifyIframeApiPromise;
        }

        spotifyIframeApiPromise = new Promise((resolve, reject) => {
            const previousReadyCallback = window.onSpotifyIframeApiReady;
            let settled = false;

            const finish = (callback, value) => {
                if (settled) {
                    return;
                }

                settled = true;
                window.clearTimeout(timeoutId);
                callback(value);
            };

            window.onSpotifyIframeApiReady = (IFrameAPI) => {
                if (typeof previousReadyCallback === 'function') {
                    previousReadyCallback(IFrameAPI);
                }
                finish(resolve, IFrameAPI);
            };

            let script = document.querySelector('script[data-spotify-iframe-api]');
            if (!script) {
                script = document.createElement('script');
                script.src = 'https://open.spotify.com/embed/iframe-api/v1';
                script.async = true;
                script.setAttribute('data-spotify-iframe-api', 'true');
                script.addEventListener('error', () => finish(reject, new Error('Spotify iFrame API could not be loaded.')));
                document.head.appendChild(script);
            }

            const timeoutId = window.setTimeout(() => {
                finish(reject, new Error('Spotify iFrame API load timeout.'));
            }, 9000);
        });

        return spotifyIframeApiPromise;
    };

    const createOrUpdateController = async (album) => {
        if (!album.spotifyUri) {
            mountIframeFallback(album);
            return;
        }

        try {
            const spotifyIframeApi = await ensureSpotifyIframeApi();

            if (!embedController) {
                await new Promise((resolve) => {
                    spotifyIframeApi.createController(embedHost, {
                        uri: album.spotifyUri,
                        width: '100%',
                        height: '352',
                    }, (controller) => {
                        embedController = controller;

                        controller.addListener('ready', () => {
                            setPlayerReadyState(true, 'Lecteur prêt');
                        });

                        controller.addListener('playback_started', () => {
                            setPlaybackState(true);
                        });

                        controller.addListener('playback_update', (event) => {
                            const data = event?.data || {};
                            const isPlaying = data.isPaused === false && data.isBuffering !== true;
                            setPlaybackState(isPlaying);
                        });

                        resolve();
                    });
                });
            } else {
                embedController.loadUri(album.spotifyUri);
                setPlayerReadyState(true, 'Lecteur prêt');
            }

            hasIframeFallback = false;
            setPlaybackState(false);
        } catch (error) {
            mountIframeFallback(album);
        }
    };

    const setAlbum = async (trigger) => {
        currentAlbum = getAlbumFromTrigger(trigger);
        applyAlbum(currentAlbum, trigger);
        setPlayerReadyState(false, 'Chargement du lecteur…');
        setPlaybackState(false);
        await createOrUpdateController(currentAlbum);
    };

    if (toggle) {
        toggle.addEventListener('click', async () => {
            if (!embedController && currentAlbum) {
                await createOrUpdateController(currentAlbum);
            }

            if (!embedController) {
                return;
            }

            embedController.togglePlay();
        });
    }

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            void setAlbum(trigger);
        });
    });

    void setAlbum(triggers[0]);
}

function initPhotoLightbox(slider, slides) {
    const dialog = document.querySelector('[data-photo-lightbox]');
    if (!dialog) return;

    const image = dialog.querySelector('[data-photo-lightbox-image]');
    const title = dialog.querySelector('[data-photo-lightbox-title]');
    const text = dialog.querySelector('[data-photo-lightbox-text]');
    const closeButton = dialog.querySelector('[data-photo-lightbox-close]');
    const prevButton = dialog.querySelector('[data-photo-lightbox-prev]');
    const nextButton = dialog.querySelector('[data-photo-lightbox-next]');

    if (!image || !title || !text) {
        return;
    }

    let currentIndex = 0;

    const openAt = (index) => {
        currentIndex = ((index % slides.length) + slides.length) % slides.length;
        const slide = slides[currentIndex];

        image.setAttribute('src', slide.getAttribute('data-photo-src') || '');
        image.setAttribute('alt', slide.getAttribute('data-photo-alt') || '');
        title.textContent = slide.getAttribute('data-photo-title') || '';
        text.textContent = slide.getAttribute('data-photo-caption') || '';

        if (typeof dialog.showModal === 'function') {
            if (!dialog.open) {
                dialog.showModal();
            }
        } else {
            dialog.setAttribute('open', 'open');
        }
    };

    const close = () => {
        if (typeof dialog.close === 'function' && dialog.open) {
            dialog.close();
            return;
        }

        dialog.removeAttribute('open');
    };

    slider.querySelectorAll('[data-photo-lightbox-open]').forEach((button, index) => {
        button.addEventListener('click', () => openAt(index));
    });

    closeButton?.addEventListener('click', close);
    prevButton?.addEventListener('click', () => openAt(currentIndex - 1));
    nextButton?.addEventListener('click', () => openAt(currentIndex + 1));

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });

    document.addEventListener('keydown', (event) => {
        const isOpen = dialog.hasAttribute('open') || dialog.open;
        if (!isOpen) return;

        if (event.key === 'ArrowLeft') {
            openAt(currentIndex - 1);
        }

        if (event.key === 'ArrowRight') {
            openAt(currentIndex + 1);
        }
    });
}
