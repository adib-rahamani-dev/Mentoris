# CSS conventions

1. Use design tokens; avoid unexplained literal colors and spacing in components.
2. Use BEM names: `.component`, `.component__part`, `.component--variant`.
3. Keep layout rules out of page-specific files when they can be generalized.
4. Keep selectors shallow and avoid IDs for styling.
5. Use logical properties (`margin-inline`, `padding-block`) for RTL compatibility.
6. Place responsive rules beside a component when they are component-specific; use `responsive.css` only for broad system rules.
7. JavaScript toggles state classes such as `.is-open` and behavior attributes such as `data-modal-open`.
