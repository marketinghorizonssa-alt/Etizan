# Performance QA scope

- Keep GTM bootstrap immediate in `<head>`; do not defer or delay Google Tag Manager.
- Inline the small local CSS files to remove stylesheet render-blocking requests.
- Preload the responsive hero image that matches the viewport.
- Build smaller mobile WebP variants for hero and about imagery.
- Increase footer text contrast for Lighthouse accessibility.
- Add a valid `/llms.txt` endpoint and declarative WebMCP form metadata for Agentic Browsing.
- Extend cache lifetime for versioned static assets.
- Preserve existing form submit logic and call/WhatsApp event names.
