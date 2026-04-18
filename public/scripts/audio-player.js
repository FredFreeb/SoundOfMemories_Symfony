/**
 * Sound Of Memories — Native Audio Player with Visualizer
 * Replaces Spotify embed with HTML5 audio + Web Audio API frequency bars.
 */
document.addEventListener('DOMContentLoaded', () => {
    const playerSection = document.querySelector('[data-som-player]');
    if (!playerSection) return;

    const state = {
        audio: new Audio(),
        audioCtx: null,
        analyser: null,
        source: null,
        tracks: [],
        currentIndex: -1,
        currentAlbumKey: '',
        isPlaying: false,
        animFrame: null,
    };

    // DOM references
    const els = {
        trackList:    playerSection.querySelector('[data-player-tracklist]'),
        playBtn:      playerSection.querySelector('[data-player-play]'),
        prevBtn:      playerSection.querySelector('[data-player-prev]'),
        nextBtn:      playerSection.querySelector('[data-player-next]'),
        title:        playerSection.querySelector('[data-player-track-title]'),
        progress:     playerSection.querySelector('[data-player-progress]'),
        progressBar:  playerSection.querySelector('[data-player-progress-bar]'),
        currentTime:  playerSection.querySelector('[data-player-current-time]'),
        duration:     playerSection.querySelector('[data-player-duration]'),
        canvas:       playerSection.querySelector('[data-player-visualizer]'),
        vinyl:        playerSection.querySelector('[data-player-vinyl]'),
        cover:        playerSection.querySelector('[data-player-cover-img]'),
        coverBg:      playerSection.querySelector('[data-player-cover-bg]'),
        albumChips:   playerSection.querySelectorAll('[data-album-select]'),
        albumTitle:   playerSection.querySelector('[data-player-album-title]'),
        albumType:    playerSection.querySelector('[data-player-album-type]'),
        albumCopy:    playerSection.querySelector('[data-player-album-copy]'),
        streamBandcamp: playerSection.querySelector('[data-player-stream-bandcamp]'),
        streamSpotify: playerSection.querySelector('[data-player-stream-spotify]'),
        streamApple:   playerSection.querySelector('[data-player-stream-apple]'),
        streamYoutube: playerSection.querySelector('[data-player-stream-youtube]'),
        streamSoundcloud: playerSection.querySelector('[data-player-stream-soundcloud]'),
        emptyState:   playerSection.querySelector('[data-player-empty-state]'),
    };

    // Parse tracks from data attributes
    const trackNodes = playerSection.querySelectorAll('[data-track-album]');
    trackNodes.forEach(node => {
        state.tracks.push({
            src: node.dataset.trackSrc || '',
            title: node.dataset.trackTitle,
            album: node.dataset.trackAlbum || '',
            available: Boolean(node.dataset.trackSrc),
        });
    });

    if (!state.tracks.length) return;

    // Audio setup
    state.audio.preload = 'metadata';
    state.audio.crossOrigin = 'anonymous';

    function getVisibleTrackIndices(albumKey = state.currentAlbumKey, availableOnly = false) {
        return state.tracks.reduce((indices, track, index) => {
            if ((!albumKey || albumKey === 'all' || track.album === albumKey) && (!availableOnly || track.available)) {
                indices.push(index);
            }

            return indices;
        }, []);
    }

    function setControlsDisabled(disabled) {
        [els.playBtn, els.prevBtn, els.nextBtn].forEach(button => {
            if (button) {
                button.disabled = disabled;
            }
        });

        if (els.progress) {
            els.progress.classList.toggle('is-disabled', disabled);
        }
    }

    function setEmptyState(visible, message = 'Pas encore de piste locale disponible pour cet album. Utilise les liens de streaming ci-dessus.') {
        if (!els.emptyState) return;

        els.emptyState.hidden = !visible;
        els.emptyState.textContent = message;
    }

    function setActiveTrack(index) {
        trackNodes.forEach((node, i) => {
            const isActive = i === index;
            node.classList.toggle('is-active', isActive);
            node.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
    }

    function initAudioContext() {
        if (state.audioCtx) return;
        state.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        state.analyser = state.audioCtx.createAnalyser();
        state.analyser.fftSize = 128;
        state.source = state.audioCtx.createMediaElementSource(state.audio);
        state.source.connect(state.analyser);
        state.analyser.connect(state.audioCtx.destination);
    }

    // Load & play a track
    function loadTrack(index) {
        if (index < 0 || index >= state.tracks.length) return false;

        const track = state.tracks[index];
        if (!track.available) return false;

        state.currentIndex = index;
        state.audio.src = track.src;

        if (els.title) els.title.textContent = track.title;
        setActiveTrack(index);

        updateTimeDisplay();
        return true;
    }

    function play() {
        if (state.currentIndex < 0) return;

        initAudioContext();
        if (state.audioCtx.state === 'suspended') {
            state.audioCtx.resume();
        }
        state.audio.play();
        state.isPlaying = true;
        updatePlayButton();
        if (els.vinyl) els.vinyl.classList.add('is-spinning');
        startVisualizer();
    }

    function pause() {
        state.audio.pause();
        state.isPlaying = false;
        updatePlayButton();
        if (els.vinyl) els.vinyl.classList.remove('is-spinning');
        stopVisualizer();
    }

    function togglePlay() {
        if (state.isPlaying) {
            pause();
        } else {
            play();
        }
    }

    function playTrack(index) {
        if (index < 0 || index >= state.tracks.length) return;

        if (!loadTrack(index)) return;
        play();
    }

    function nextTrack() {
        const visibleIndices = getVisibleTrackIndices(state.currentAlbumKey, true);
        if (!visibleIndices.length) return;

        const currentPosition = visibleIndices.indexOf(state.currentIndex);
        const nextPosition = currentPosition === -1 ? 0 : (currentPosition + 1) % visibleIndices.length;

        playTrack(visibleIndices[nextPosition]);
    }

    function prevTrack() {
        const visibleIndices = getVisibleTrackIndices(state.currentAlbumKey, true);
        if (!visibleIndices.length) return;

        if (state.audio.currentTime > 3) {
            state.audio.currentTime = 0;
            return;
        }

        const currentPosition = visibleIndices.indexOf(state.currentIndex);
        const prevPosition = currentPosition === -1 ? visibleIndices.length - 1 : (currentPosition - 1 + visibleIndices.length) % visibleIndices.length;

        playTrack(visibleIndices[prevPosition]);
    }

    function updatePlayButton() {
        if (!els.playBtn) return;
        const icon = els.playBtn.querySelector('.player-btn__icon');
        const label = els.playBtn.querySelector('.player-btn__label');
        if (icon) icon.textContent = state.isPlaying ? '❚❚' : '▶';
        if (label) label.textContent = state.isPlaying ? 'Pause' : 'Play';
        els.playBtn.setAttribute('aria-pressed', state.isPlaying ? 'true' : 'false');
    }

    // Time formatting
    function formatTime(seconds) {
        if (isNaN(seconds) || !isFinite(seconds)) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s < 10 ? '0' : ''}${s}`;
    }

    function updateTimeDisplay() {
        if (els.currentTime) els.currentTime.textContent = formatTime(state.audio.currentTime);
        if (els.duration) els.duration.textContent = formatTime(state.audio.duration);

        if (els.progressBar && state.audio.duration) {
            const pct = (state.audio.currentTime / state.audio.duration) * 100;
            els.progressBar.style.width = `${pct}%`;
            return;
        }

        if (els.progressBar) {
            els.progressBar.style.width = '0%';
        }
    }

    // Visualizer
    function startVisualizer() {
        if (!els.canvas || !state.analyser) return;
        const ctx = els.canvas.getContext('2d');
        const bufferLength = state.analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);

        function draw() {
            state.animFrame = requestAnimationFrame(draw);
            state.analyser.getByteFrequencyData(dataArray);

            const w = els.canvas.width;
            const h = els.canvas.height;
            ctx.clearRect(0, 0, w, h);

            const barCount = bufferLength;
            const barWidth = w / barCount;
            const gap = 2;

            for (let i = 0; i < barCount; i++) {
                const barHeight = (dataArray[i] / 255) * h * 0.9;
                const x = i * barWidth;

                // Gradient from accent orange to ice cyan
                const ratio = i / barCount;
                const r = Math.floor(255 - ratio * 176);
                const g = Math.floor(107 + ratio * 135);
                const b = Math.floor(53 + ratio * 170);

                ctx.fillStyle = `rgba(${r}, ${g}, ${b}, 0.85)`;
                ctx.fillRect(x + gap / 2, h - barHeight, barWidth - gap, barHeight);

                // Mirror reflection
                ctx.fillStyle = `rgba(${r}, ${g}, ${b}, 0.15)`;
                ctx.fillRect(x + gap / 2, h, barWidth - gap, barHeight * 0.3);
            }
        }
        draw();
    }

    function stopVisualizer() {
        if (state.animFrame) {
            cancelAnimationFrame(state.animFrame);
            state.animFrame = null;
        }
        // Draw idle state
        if (els.canvas) {
            const ctx = els.canvas.getContext('2d');
            const w = els.canvas.width;
            const h = els.canvas.height;
            ctx.clearRect(0, 0, w, h);

            const barCount = 64;
            const barWidth = w / barCount;
            for (let i = 0; i < barCount; i++) {
                const barHeight = 2 + Math.sin(i * 0.3) * 4;
                const x = i * barWidth;
                ctx.fillStyle = 'rgba(255, 107, 53, 0.2)';
                ctx.fillRect(x + 1, h - barHeight, barWidth - 2, barHeight);
            }
        }
    }

    function resizeCanvas() {
        if (!els.canvas) return;
        const rect = els.canvas.parentElement.getBoundingClientRect();
        els.canvas.width = rect.width * window.devicePixelRatio;
        els.canvas.height = rect.height * window.devicePixelRatio;
        els.canvas.style.width = rect.width + 'px';
        els.canvas.style.height = rect.height + 'px';
        if (!state.isPlaying) stopVisualizer();
    }

    // Album switching
    function switchAlbum(chip) {
        if (!chip) return;

        const cover = chip.dataset.albumCover;
        const title = chip.dataset.albumTitle;
        const type = chip.dataset.albumType;
        const copy = chip.dataset.albumCopy;
        const albumKey = chip.dataset.albumKey || '';
        const bandcamp = chip.dataset.albumBandcamp;
        const spotify = chip.dataset.albumSpotify;
        const apple = chip.dataset.albumApple;
        const youtube = chip.dataset.albumYoutube;
        const soundcloud = chip.dataset.albumSoundcloud;

        state.currentAlbumKey = albumKey;

        if (els.cover) {
            els.cover.src = cover;
            els.cover.alt = title;
        }
        if (els.coverBg) els.coverBg.style.setProperty('--album-cover-url', `url('${cover}')`);
        if (els.albumTitle) els.albumTitle.textContent = title;
        if (els.albumType) els.albumType.textContent = type;
        if (els.albumCopy) els.albumCopy.textContent = copy;
        if (els.streamBandcamp && bandcamp) els.streamBandcamp.href = bandcamp;
        if (els.streamSpotify && spotify) els.streamSpotify.href = spotify;
        if (els.streamApple && apple) els.streamApple.href = apple;
        if (els.streamYoutube && youtube) els.streamYoutube.href = youtube;
        if (els.streamSoundcloud && soundcloud) els.streamSoundcloud.href = soundcloud;

        els.albumChips.forEach(c => {
            c.classList.toggle('is-active', c === chip);
            c.setAttribute('aria-selected', c === chip ? 'true' : 'false');
        });

        // Filter tracks for this album
        const availableVisibleIndices = getVisibleTrackIndices(albumKey, true);
        trackNodes.forEach((node, index) => {
            const belongs = !albumKey || node.dataset.trackAlbum === albumKey || albumKey === 'all';
            const unavailable = belongs && !state.tracks[index].available;

            node.hidden = !belongs;
            node.disabled = !belongs || unavailable;
            node.classList.toggle('is-unavailable', unavailable);
        });

        if (!availableVisibleIndices.length) {
            pause();
            state.audio.removeAttribute('src');
            state.audio.load();
            state.currentIndex = -1;
            setActiveTrack(-1);
            if (els.title) els.title.textContent = `Aucune piste locale pour ${title}`;
            updateTimeDisplay();
            setControlsDisabled(true);
            setEmptyState(true, `Pas encore de piste locale disponible pour ${title}. Utilise les liens de streaming ci-dessus.`);
            return;
        }

        setControlsDisabled(false);
        setEmptyState(false);

        const currentTrackIsVisible = availableVisibleIndices.includes(state.currentIndex);
        const shouldResumePlayback = state.isPlaying;

        if (!currentTrackIsVisible) {
            if (shouldResumePlayback) {
                playTrack(availableVisibleIndices[0]);
            } else {
                loadTrack(availableVisibleIndices[0]);
            }
        }
    }

    // Progress bar seeking
    if (els.progress) {
        els.progress.addEventListener('click', (e) => {
            const rect = els.progress.getBoundingClientRect();
            const pct = (e.clientX - rect.left) / rect.width;
            state.audio.currentTime = pct * state.audio.duration;
        });
    }

    // Event listeners
    if (els.playBtn) els.playBtn.addEventListener('click', togglePlay);
    if (els.prevBtn) els.prevBtn.addEventListener('click', prevTrack);
    if (els.nextBtn) els.nextBtn.addEventListener('click', nextTrack);

    trackNodes.forEach((node, i) => {
        node.addEventListener('click', () => playTrack(i));
    });

    els.albumChips.forEach(chip => {
        chip.addEventListener('click', () => switchAlbum(chip));
    });

    state.audio.addEventListener('timeupdate', updateTimeDisplay);
    state.audio.addEventListener('ended', nextTrack);
    state.audio.addEventListener('loadedmetadata', updateTimeDisplay);

    window.addEventListener('resize', resizeCanvas);

    // Init
    switchAlbum(playerSection.querySelector('[data-album-select].is-active') || els.albumChips[0]);
    resizeCanvas();
    stopVisualizer(); // draw idle bars
});
