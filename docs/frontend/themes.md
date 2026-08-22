# Light and Dark Themes

Mentoris now supports light and dark color modes through the shared design tokens. The inline bootstrap in the main layout applies the saved preference before CSS renders, avoiding a theme flash. Without a saved preference, the operating-system `prefers-color-scheme` setting is used.

The navbar toggle updates `data-theme` on the root element, persists the choice under `mentoris-theme` in local storage, updates its accessibility state, and synchronizes the browser theme-color metadata. Component-specific light overrides are kept in `public/assets/css/theme.css` after all page styles.
