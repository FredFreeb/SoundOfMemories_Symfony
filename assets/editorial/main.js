import './styles/editorial.css';

const editorialBooters = {
  'story-block': bootStoryBlock,
  'editorial-probe': bootStoryBlock,
};

let pretextModulePromise = null;

function onReady(callback) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback, { once: true });

    return;
  }

  callback();
}

function getPretextModule() {
  if (!pretextModulePromise) {
    pretextModulePromise = import('@chenglou/pretext');
  }

  return pretextModulePromise;
}

function getLineHeight(element) {
  const computedStyle = window.getComputedStyle(element);
  const lineHeight = Number.parseFloat(computedStyle.lineHeight);

  if (Number.isFinite(lineHeight)) {
    return lineHeight;
  }

  const fontSize = Number.parseFloat(computedStyle.fontSize);

  return Number.isFinite(fontSize) ? fontSize * 1.4 : 24;
}

async function measureCopy(element) {
  const copyNode = element.querySelector('[data-editorial-copy]');

  if (!(copyNode instanceof HTMLElement)) {
    return;
  }

  const availableWidth = Math.round(copyNode.clientWidth);

  if (availableWidth <= 0) {
    return;
  }

  const text = copyNode.textContent?.trim() ?? '';

  if (text === '') {
    return;
  }

  const { prepare, layout } = await getPretextModule();
  const font = window.getComputedStyle(copyNode).font;
  const prepared = prepare(text, font);
  const lineHeight = getLineHeight(copyNode);
  const metrics = layout(prepared, availableWidth, lineHeight);

  element.dataset.editorialMeasured = 'true';
  element.style.setProperty('--editorial-measured-height', `${metrics.height}px`);
  element.style.setProperty('--editorial-line-count', `${metrics.lineCount}`);
}

function bootStoryBlock(element) {
  let frame = 0;

  const requestMeasure = () => {
    window.cancelAnimationFrame(frame);
    frame = window.requestAnimationFrame(() => {
      void measureCopy(element);
    });
  };

  const resizeObserver = new ResizeObserver(requestMeasure);
  resizeObserver.observe(element);

  if (document.fonts?.ready) {
    document.fonts.ready.then(requestMeasure).catch(() => requestMeasure());
  } else {
    requestMeasure();
  }
}

function bootEditorialRuntime() {
  document.documentElement.classList.add('editorial-runtime-ready');

  document.querySelectorAll('[data-editorial-module]').forEach((element) => {
    if (!(element instanceof HTMLElement)) {
      return;
    }

    const moduleName = element.dataset.editorialModule;

    if (!moduleName) {
      return;
    }

    const booter = editorialBooters[moduleName];

    if (!booter) {
      return;
    }

    element.dataset.editorialHydrated = 'true';
    booter(element);
  });
}

onReady(bootEditorialRuntime);
