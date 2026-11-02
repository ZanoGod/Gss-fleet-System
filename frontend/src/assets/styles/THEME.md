# Frontend theme tokens

`src/assets/styles/theme.css` is imported once from `src/main.ts`, after Bootstrap CSS and before the app styles. It is the only file needed to choose a preset. `themes/warm.css` preserves the original look; `themes/ocean.css` and `themes/navy.css` use the supplied palettes; `themes/professional.css` uses GSS logo yellow and orange, cocoa brown actions, and warm beige and cream surfaces. All presets use the same variables, so components do not need edits when switching. Page layout, typography, spacing, and responsive rules are unchanged.

## Switch the whole app theme

Keep exactly one preset import active in `theme.css`.

The professional palette is currently active:

```css
/* @import './themes/warm.css'; */
/* @import './themes/ocean.css'; */
/* @import './themes/navy.css'; */
@import './themes/professional.css';
```

To restore the original warm palette, use:

```css
@import './themes/warm.css';
/* @import './themes/ocean.css'; */
/* @import './themes/navy.css'; */
/* @import './themes/professional.css'; */
```

Keep exactly one import active. Save the file and Vite reloads the app. The ocean and navy presets import the warm token base and override it. The professional preset contains the same semantic tokens with the GSS cocoa and warm-neutral palette.

## Supplied and professional palette codes

| Preset       | Brand colors          | Action color                                  | Background | Surface   |
| ------------ | --------------------- | --------------------------------------------- | ---------- | --------- |
| Ocean        | `#3368A0` / `#66A3BF` | `#3368A0`                                     | `#F2EFE7`  | `#C8DFDB` |
| Navy         | `#405D72` / `#758694` | `#405D72`                                     | `#FFF8F3`  | `#F7E7DC` |
| Professional | `#FFD93D` / `#FF9A00` | `#4F200D` (hover `#64351E`, active `#783F23`) | `#EFE9E0`  | `#FFFDFA` |

## Customize a palette

Edit the active preset file and change its semantic `--gss-*` values. Components always use the same variable names:

- Brand accents: `--gss-brand-primary`, `--gss-brand-secondary`.
- Sidebar, header, and page: `--gss-sidebar-background`, `--gss-sidebar-text`, `--gss-header-background`, `--gss-header-text`, `--gss-page-background`.
- Cards, text, and borders: `--gss-surface-*`, `--gss-text-*`, `--gss-border-*`.
- Buttons: `--gss-button-primary-*` and `--gss-button-outline-*`.
- Inputs, tables, and status badges: `--gss-input-*`, `--gss-table-*`, and `--gss-status-*`.
- Legacy one-off colors are kept under `--gss-palette-*` tokens.

Bootstrap primary/link colors follow the active preset. Other Bootstrap status colors retain their original palette. RGB, emphasis, and subtle background variables remain grouped in the preset files.

A future theme can override the same tokens on `[data-theme="light"]`, `[data-theme="dark"]`, or `[data-theme="custom"]` on `<html>`. No theme switching UI is enabled.

## Original authored color mapping

The semantic entries in `themes/warm.css` carry the original values for the major UI roles. Its support palette entries preserve the less common original colors exactly, including alpha colors and Bootstrap adaptations.

| Variable                               | Original value                                                                                                                                                                                                  |
| -------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `--gss-brand-primary`                  | `#ffd93d`                                                                                                                                                                                                       |
| `--gss-brand-secondary`                | `#ff9a00`                                                                                                                                                                                                       |
| `--gss-primary`                        | `#ffd93d`                                                                                                                                                                                                       |
| `--gss-secondary`                      | `#ff9a00`                                                                                                                                                                                                       |
| `--gss-text-strong`                    | `#4f200d`                                                                                                                                                                                                       |
| `--gss-text-secondary`                 | `#7b4322`                                                                                                                                                                                                       |
| `--gss-text-body`                      | `#3d2418`                                                                                                                                                                                                       |
| `--gss-text-muted`                     | `#8f735f`                                                                                                                                                                                                       |
| `--gss-text-label`                     | `#704523`                                                                                                                                                                                                       |
| `--gss-sidebar-background`             | `linear-gradient(180deg, #4f200d, #7b4322)`                                                                                                                                                                     |
| `--gss-sidebar-text`                   | `#f6f1e9`                                                                                                                                                                                                       |
| `--gss-header-background`              | `transparent`                                                                                                                                                                                                   |
| `--gss-header-text`                    | `#3d2418`                                                                                                                                                                                                       |
| `--gss-page-background`                | `radial-gradient(circle at top left, rgba(255, 200, 70, 0.22), transparent 42%), radial-gradient(circle at bottom right, rgba(255, 150, 40, 0.16), transparent 40%), linear-gradient(180deg, #f7f5f1, #efece5)` |
| `--gss-surface-card`                   | `#fffdf9`                                                                                                                                                                                                       |
| `--gss-surface-card-header`            | `#fffaf3`                                                                                                                                                                                                       |
| `--gss-surface-card-overlay`           | `rgba(255, 253, 249, 0.97)`                                                                                                                                                                                     |
| `--gss-border-default`                 | `#e7dac7`                                                                                                                                                                                                       |
| `--gss-border-default-alpha`           | `rgba(79, 32, 13, 0.12)`                                                                                                                                                                                        |
| `--gss-button-default-text`            | `#4f200d`                                                                                                                                                                                                       |
| `--gss-button-primary-bg`              | `#ffd76a`                                                                                                                                                                                                       |
| `--gss-button-primary-bg-end`          | `#f0b534`                                                                                                                                                                                                       |
| `--gss-button-primary-border`          | `#edbf4c`                                                                                                                                                                                                       |
| `--gss-button-primary-hover-bg`        | `#ffcd4e`                                                                                                                                                                                                       |
| `--gss-button-primary-hover-border`    | `#d9a72d`                                                                                                                                                                                                       |
| `--gss-button-primary-active-bg`       | `#f0bb37`                                                                                                                                                                                                       |
| `--gss-button-primary-disabled-bg`     | `#ffe4a1`                                                                                                                                                                                                       |
| `--gss-button-primary-disabled-border` | `#eddaac`                                                                                                                                                                                                       |
| `--gss-button-outline-text`            | `#704523`                                                                                                                                                                                                       |
| `--gss-button-outline-border`          | `#dfd2c2`                                                                                                                                                                                                       |
| `--gss-button-outline-hover-bg`        | `#f6eddf`                                                                                                                                                                                                       |
| `--gss-button-outline-hover-border`    | `#cbb799`                                                                                                                                                                                                       |
| `--gss-button-outline-active-bg`       | `#efe1cc`                                                                                                                                                                                                       |
| `--gss-button-outline-disabled-text`   | `#9d8c7c`                                                                                                                                                                                                       |
| `--gss-button-outline-disabled-border` | `#e5dacd`                                                                                                                                                                                                       |
| `--gss-form-background`                | `#fffdfa`                                                                                                                                                                                                       |
| `--gss-input-background`               | `#fffdfa`                                                                                                                                                                                                       |
| `--gss-input-text`                     | `#3d2418`                                                                                                                                                                                                       |
| `--gss-input-border`                   | `#e3d8ca`                                                                                                                                                                                                       |
| `--gss-input-placeholder`              | `#94816f`                                                                                                                                                                                                       |
| `--gss-input-focus-border`             | `#c89a36`                                                                                                                                                                                                       |
| `--gss-input-checked`                  | `#8c5b2e`                                                                                                                                                                                                       |
| `--gss-table-background`               | `transparent`                                                                                                                                                                                                   |
| `--gss-table-text`                     | `#3d2418`                                                                                                                                                                                                       |
| `--gss-table-border`                   | `#eee5da`                                                                                                                                                                                                       |
| `--gss-table-hover`                    | `#fff6df`                                                                                                                                                                                                       |
| `--gss-table-header-background`        | `#f5ecde`                                                                                                                                                                                                       |
| `--gss-table-header-text`              | `#7d5c3d`                                                                                                                                                                                                       |
| `--gss-status-success-background`      | `#e4f4e8`                                                                                                                                                                                                       |
| `--gss-status-success-text`            | `#246338`                                                                                                                                                                                                       |
| `--gss-status-warning-background`      | `#fff1d0`                                                                                                                                                                                                       |
| `--gss-status-warning-text`            | `#80540d`                                                                                                                                                                                                       |
| `--gss-status-danger-background`       | `#fde5e1`                                                                                                                                                                                                       |
| `--gss-status-danger-text`             | `#8d3024`                                                                                                                                                                                                       |
| `--gss-status-info-background`         | `#cff4fc`                                                                                                                                                                                                       |
| `--gss-status-info-text`               | `#055160`                                                                                                                                                                                                       |
| `--gss-shadow-tint-soft`               | `rgba(79, 32, 13, 0.08)`                                                                                                                                                                                        |
| `--gss-shadow-tint-subtle`             | `rgba(79, 32, 13, 0.04)`                                                                                                                                                                                        |
| `--gss-shadow-tint-medium`             | `rgba(79, 32, 13, 0.06)`                                                                                                                                                                                        |
| `--gss-bs-primary-hover`               | `#0b5ed7`                                                                                                                                                                                                       |
| `--gss-bs-primary-active`              | `#0a58ca`                                                                                                                                                                                                       |
| `--gss-bs-primary-active-border`       | `#0a53be`                                                                                                                                                                                                       |
| `--gss-bs-secondary-hover`             | `#5c636a`                                                                                                                                                                                                       |
| `--gss-bs-secondary-hover-border`      | `#565e64`                                                                                                                                                                                                       |
| `--gss-bs-secondary-active-border`     | `#51585e`                                                                                                                                                                                                       |
| `--gss-bs-success-hover`               | `#157347`                                                                                                                                                                                                       |
| `--gss-bs-success-hover-border`        | `#146c43`                                                                                                                                                                                                       |
| `--gss-bs-success-active`              | `#146c43`                                                                                                                                                                                                       |
| `--gss-bs-success-active-border`       | `#13653f`                                                                                                                                                                                                       |
| `--gss-bs-danger-hover`                | `#bb2d3b`                                                                                                                                                                                                       |
| `--gss-bs-danger-hover-border`         | `#b02a37`                                                                                                                                                                                                       |
| `--gss-bs-danger-active`               | `#b02a37`                                                                                                                                                                                                       |
| `--gss-bs-danger-active-border`        | `#a52834`                                                                                                                                                                                                       |
| `--gss-bs-warning-hover`               | `#ffca2c`                                                                                                                                                                                                       |
| `--gss-bs-warning-hover-border`        | `#ffc720`                                                                                                                                                                                                       |
| `--gss-bs-warning-active`              | `#ffcd39`                                                                                                                                                                                                       |
| `--gss-bs-warning-active-border`       | `#ffc720`                                                                                                                                                                                                       |
| `--gss-bs-info-hover`                  | `#31d2f2`                                                                                                                                                                                                       |
| `--gss-bs-info-hover-border`           | `#25cff2`                                                                                                                                                                                                       |
| `--gss-bs-info-active`                 | `#3dd5f3`                                                                                                                                                                                                       |
| `--gss-bs-info-active-border`          | `#25cff2`                                                                                                                                                                                                       |
| `--gss-bs-light-hover`                 | `#d3d4d5`                                                                                                                                                                                                       |
| `--gss-bs-light-hover-border`          | `#c6c7c8`                                                                                                                                                                                                       |
| `--gss-bs-light-active-border`         | `#babbbc`                                                                                                                                                                                                       |
| `--gss-bs-dark-hover`                  | `#424649`                                                                                                                                                                                                       |
| `--gss-bs-dark-hover-border`           | `#373b3e`                                                                                                                                                                                                       |
| `--gss-bs-dark-active`                 | `#4d5154`                                                                                                                                                                                                       |
| `--gss-palette-24160e33`               | `#24160e33`                                                                                                                                                                                                     |
| `--gss-palette-24160e80`               | `#24160e80`                                                                                                                                                                                                     |
| `--gss-palette-24160eb3`               | `#24160eb3`                                                                                                                                                                                                     |
| `--gss-palette-4f200d09`               | `#4f200d09`                                                                                                                                                                                                     |
| `--gss-palette-57483f`                 | `#57483f`                                                                                                                                                                                                       |
| `--gss-palette-70411d`                 | `#70411d`                                                                                                                                                                                                       |
| `--gss-palette-785025`                 | `#785025`                                                                                                                                                                                                       |
| `--gss-palette-806957`                 | `#806957`                                                                                                                                                                                                       |
| `--gss-palette-806e5d`                 | `#806e5d`                                                                                                                                                                                                       |
| `--gss-palette-856c55`                 | `#856c55`                                                                                                                                                                                                       |
| `--gss-palette-89776c`                 | `#89776c`                                                                                                                                                                                                       |
| `--gss-palette-8c8178`                 | `#8c8178`                                                                                                                                                                                                       |
| `--gss-palette-946b25`                 | `#946b25`                                                                                                                                                                                                       |
| `--gss-palette-9c9187`                 | `#9c9187`                                                                                                                                                                                                       |
| `--gss-palette-9d8c7c`                 | `#9d8c7c`                                                                                                                                                                                                       |
| `--gss-palette-b8a797`                 | `#b8a797`                                                                                                                                                                                                       |
| `--gss-palette-cbb799`                 | `#cbb799`                                                                                                                                                                                                       |
| `--gss-palette-dfd2c2`                 | `#dfd2c2`                                                                                                                                                                                                       |
| `--gss-palette-e5dacd`                 | `#e5dacd`                                                                                                                                                                                                       |
| `--gss-palette-e7e1d8`                 | `#e7e1d8`                                                                                                                                                                                                       |
| `--gss-palette-e8b33b22`               | `#e8b33b22`                                                                                                                                                                                                     |
| `--gss-palette-ead7a7`                 | `#ead7a7`                                                                                                                                                                                                       |
| `--gss-palette-eee`                    | `#eee`                                                                                                                                                                                                          |
| `--gss-palette-efbe4626`               | `#efbe4626`                                                                                                                                                                                                     |
| `--gss-palette-efe1cc`                 | `#efe1cc`                                                                                                                                                                                                       |
| `--gss-palette-efe5d9`                 | `#efe5d9`                                                                                                                                                                                                       |
| `--gss-palette-efece5`                 | `#efece5`                                                                                                                                                                                                       |
| `--gss-palette-f0c14b`                 | `#f0c14b`                                                                                                                                                                                                       |
| `--gss-palette-f3eadd`                 | `#f3eadd`                                                                                                                                                                                                       |
| `--gss-palette-f4d7a1`                 | `#f4d7a1`                                                                                                                                                                                                       |
| `--gss-palette-f6eddf`                 | `#f6eddf`                                                                                                                                                                                                       |
| `--gss-palette-f7f5f1`                 | `#f7f5f1`                                                                                                                                                                                                       |
| `--gss-palette-fff1c9`                 | `#fff1c9`                                                                                                                                                                                                       |
| `--gss-palette-fff3cd`                 | `#fff3cd`                                                                                                                                                                                                       |
| `--gss-palette-fff5e3`                 | `#fff5e3`                                                                                                                                                                                                       |
| `--gss-palette-rgba-0-0-0-p08`         | `rgba(0, 0, 0, 0.08)`                                                                                                                                                                                           |
| `--gss-palette-rgba-240-181-52-p2`     | `rgba(240, 181, 52, 0.2)`                                                                                                                                                                                       |
| `--gss-palette-rgba-240-181-52-p26`    | `rgba(240, 181, 52, 0.26)`                                                                                                                                                                                      |
| `--gss-palette-rgba-240-193-75-p14`    | `rgba(240, 193, 75, 0.14)`                                                                                                                                                                                      |
| `--gss-palette-rgba-255-119-95-p4`     | `rgba(255, 119, 95, 0.4)`                                                                                                                                                                                       |
| `--gss-palette-rgba-255-150-40-p16`    | `rgba(255, 150, 40, 0.16)`                                                                                                                                                                                      |
| `--gss-palette-rgba-255-154-0-24`      | `rgba(255, 154, 0, .24)`                                                                                                                                                                                        |
| `--gss-palette-rgba-255-200-70-p22`    | `rgba(255, 200, 70, 0.22)`                                                                                                                                                                                      |
| `--gss-palette-rgba-255-217-61-2`      | `rgba(255, 217, 61, .2)`                                                                                                                                                                                        |
| `--gss-palette-rgba-255-217-61-35`     | `rgba(255, 217, 61, .35)`                                                                                                                                                                                       |
| `--gss-palette-rgba-255-255-255-12`    | `rgba(255, 255, 255, .12)`                                                                                                                                                                                      |
| `--gss-palette-rgba-255-255-255-22`    | `rgba(255, 255, 255, .22)`                                                                                                                                                                                      |
| `--gss-palette-rgba-255-255-255-8`     | `rgba(255, 255, 255, .08)`                                                                                                                                                                                      |
| `--gss-palette-rgba-255-255-255-p09`   | `rgba(255, 255, 255, 0.09)`                                                                                                                                                                                     |
| `--gss-palette-rgba-255-255-255-p13`   | `rgba(255, 255, 255, 0.13)`                                                                                                                                                                                     |
| `--gss-palette-rgba-255-255-255-p7`    | `rgba(255, 255, 255, 0.7)`                                                                                                                                                                                      |
| `--gss-palette-rgba-255-255-255-p82`   | `rgba(255, 255, 255, 0.82)`                                                                                                                                                                                     |
| `--gss-palette-rgba-255-255-255-p95`   | `rgba(255, 255, 255, 0.95)`                                                                                                                                                                                     |
| `--gss-palette-rgba-30-15-5-p55`       | `rgba(30, 15, 5, 0.55)`                                                                                                                                                                                         |
| `--gss-palette-rgba-32-13-5-18`        | `rgba(32, 13, 5, .18)`                                                                                                                                                                                          |
| `--gss-palette-rgba-32-14-7-54`        | `rgba(32, 14, 7, .54)`                                                                                                                                                                                          |
| `--gss-palette-rgba-79-32-13-8`        | `rgba(79, 32, 13, .08)`                                                                                                                                                                                         |

Legacy aliases (`--cream`, `--gold`, `--amber`, `--cocoa`, `--cocoa-soft`, `--paper`, `--paper-strong`, `--ink`, `--muted`, `--line`, `--shadow`) remain available to existing layout and scoped Vue rules. Their values now resolve through the central semantic theme.

## Bootstrap default color mapping

The Bootstrap 5.3.8 defaults below match the installed Bootstrap CSS and remain the default. The related RGB variables are listed in `theme.css` and hold the equivalent channel values. GSS module buttons keep the existing gold-and-cocoa overrides.

| Variable                           | Original value |
| ---------------------------------- | -------------- |
| `--bs-primary`                     | `#0d6efd`      |
| `--bs-secondary`                   | `#6c757d`      |
| `--bs-success`                     | `#198754`      |
| `--bs-info`                        | `#0dcaf0`      |
| `--bs-warning`                     | `#ffc107`      |
| `--bs-danger`                      | `#dc3545`      |
| `--bs-light`                       | `#f8f9fa`      |
| `--bs-dark`                        | `#212529`      |
| `--bs-link-color`                  | `#0d6efd`      |
| `--bs-link-hover-color`            | `#0a58ca`      |
| `--bs-white`                       | `#fff`         |
| `--bs-black`                       | `#000`         |
| `--bs-primary-text-emphasis`       | `#052c65`      |
| `--bs-primary-bg-subtle`           | `#cfe2ff`      |
| `--bs-primary-border-subtle`       | `#9ec5fe`      |
| `--bs-secondary-text-emphasis`     | `#2b2f32`      |
| `--bs-secondary-bg-subtle`         | `#e2e3e5`      |
| `--bs-secondary-border-subtle`     | `#c4c8cb`      |
| `--bs-success-text-emphasis`       | `#0a3622`      |
| `--bs-success-bg-subtle`           | `#d1e7dd`      |
| `--bs-success-border-subtle`       | `#a3cfbb`      |
| `--bs-danger-text-emphasis`        | `#58151c`      |
| `--bs-danger-bg-subtle`            | `#f8d7da`      |
| `--bs-danger-border-subtle`        | `#f1aeb5`      |
| `--bs-warning-text-emphasis`       | `#664d03`      |
| `--bs-warning-bg-subtle`           | `#fff3cd`      |
| `--bs-warning-border-subtle`       | `#ffe69c`      |
| `--bs-info-text-emphasis`          | `#055160`      |
| `--bs-info-bg-subtle`              | `#cff4fc`      |
| `--bs-info-border-subtle`          | `#9eeaf9`      |
| `--gss-bs-primary-hover`           | `#0b5ed7`      |
| `--gss-bs-primary-active`          | `#0a58ca`      |
| `--gss-bs-primary-active-border`   | `#0a53be`      |
| `--gss-bs-secondary-hover`         | `#5c636a`      |
| `--gss-bs-secondary-hover-border`  | `#565e64`      |
| `--gss-bs-secondary-active-border` | `#51585e`      |
| `--gss-bs-success-hover`           | `#157347`      |
| `--gss-bs-success-hover-border`    | `#146c43`      |
| `--gss-bs-success-active`          | `#146c43`      |
| `--gss-bs-success-active-border`   | `#13653f`      |
| `--gss-bs-danger-hover`            | `#bb2d3b`      |
| `--gss-bs-danger-hover-border`     | `#b02a37`      |
| `--gss-bs-danger-active`           | `#b02a37`      |
| `--gss-bs-danger-active-border`    | `#a52834`      |
| `--gss-bs-warning-hover`           | `#ffca2c`      |
| `--gss-bs-warning-hover-border`    | `#ffc720`      |
| `--gss-bs-warning-active`          | `#ffcd39`      |
| `--gss-bs-warning-active-border`   | `#ffc720`      |
| `--gss-bs-info-hover`              | `#31d2f2`      |
| `--gss-bs-info-hover-border`       | `#25cff2`      |
| `--gss-bs-info-active`             | `#3dd5f3`      |
| `--gss-bs-info-active-border`      | `#25cff2`      |
| `--gss-bs-light-hover`             | `#d3d4d5`      |
| `--gss-bs-light-hover-border`      | `#c6c7c8`      |
| `--gss-bs-light-active-border`     | `#babbbc`      |
| `--gss-bs-dark-hover`              | `#424649`      |
| `--gss-bs-dark-hover-border`       | `#373b3e`      |
| `--gss-bs-dark-active`             | `#4d5154`      |
