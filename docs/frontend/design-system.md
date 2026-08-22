# Mentoris Design System

The visual language is based on a calm dark canvas, restrained violet highlights, low-contrast borders, layered surfaces, and Persian-first typography.

## CSS dependency order

```text
variables -> reset/typography/global -> layout -> components -> sections -> pages -> responsive
```

`public/assets/css/app.css` is the single browser entry point and preserves this order.

## Design tokens

All reusable decisions live in `variables.css`:

- Brand scale: `--color-brand-50` through `--color-brand-900`
- Surfaces: `--color-bg`, `--color-surface-*`, borders and overlays
- Semantic colors: success, warning, danger and info
- Type scale: xs through 4xl, weights and line heights
- Spacing: a 4px base scale from `--space-1` through `--space-24`
- Shape: xs through xl and full radii
- Elevation: small, medium, large and brand glow shadows
- Motion: shared durations and easing curves
- Layout: container widths, header height and z-index layers

Do not place raw brand colors, spacing values, radii, or shadows in page styles. Add or reuse a token instead.

## Component API

Components use BEM-style class names and modifier classes:

```html
<button class="btn btn--primary btn--lg">Action</button>
<span class="badge badge--success">Active</span>
<article class="card">...</article>
```

Interactive JavaScript components use `data-*` attributes for behavior and classes only for presentation. The supported modules are Modal, Dropdown, Tabs, Accordion, Slider, Toast, Navbar and Reveal.

## Accessibility contract

- Every interactive control must have an accessible name.
- Visible focus styles must not be removed.
- Modal and menu controls must support Escape.
- Tabs support arrows, Home, and End.
- Motion must respect `prefers-reduced-motion`.
- Text and essential UI boundaries should meet WCAG AA contrast.

## Responsive breakpoints

- Mobile: up to 640px
- Tablet: 641px to 900/960px depending on the component
- Desktop navigation: above 980px
- Maximum content width: 1320px
