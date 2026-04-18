# Editorial Runtime

This project keeps Symfony + Twig as the canonical rendering layer.

`Vite + Pretext` only powers premium editorial modules on top of the server-rendered HTML.

## Commands

Install dependencies:

```bash
npm install
```

If you don't have Node locally, use Docker:

```bash
bin/editorial-build
```

Start the editorial dev server:

```bash
npm run dev
```

Docker alternative:

```bash
bin/editorial-dev
```

Build production assets:

```bash
npm run build
```

## Current entry

- `assets/editorial/main.js`

## Twig helpers

- `vite_entry_link_tags('assets/editorial/main.js')`
- `vite_entry_script_tags('assets/editorial/main.js')`

They stay silent until `public/build/.vite/manifest.json` exists, so the existing site keeps working even before the first Vite build.

## First target

The first full editorial page planned for this runtime is:

- `/le-groupe`

The initial runtime already supports opt-in modules using:

```html
<section data-editorial-module="story-block">
  <div data-editorial-copy>...</div>
</section>
```

The current boot code measures copy blocks with Pretext and exposes:

- `--editorial-measured-height`
- `--editorial-line-count`

These CSS variables will be reused for the first premium layouts.
