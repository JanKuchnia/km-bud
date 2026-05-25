# PLAN-performance.md — Performance Optimization Plan

This plan outlines the strategy to optimize the performance of the KM-BUD website to achieve Lighthouse scores above **90+** on both Mobile and Desktop devices, leveraging the production URL: `https://km-bud.infinityfree.me/?i=1`.

## Baseline Performance Analysis
The current Lighthouse audit scores are around **55-56%** for both mobile and desktop with the following metrics:
- **First Contentful Paint (FCP)**: ~10.5 - 10.7 seconds
- **Largest Contentful Paint (LCP)**: ~17.6 - 20.7 seconds
- **Total Blocking Time (TBT)**: ~20 ms (Excellent)
- **Cumulative Layout Shift (CLS)**: ~0 (Excellent)

### Key Bottlenecks Identified
1. **Free Hosting Challenge Redirect (Lighthouse Artifact)**: The hosting platform (`infinityfree.me`) uses a security system that redirects headless browsers and inserts a challenge page (`?i=2`), causing an artificial 8-10 seconds latency in automated tests.
2. **Gigantic Inline Base64 Font in blocking CSS**: The `style.css` file is **1.23 MB** because it embeds massive binary font files (like "Inter") as base64 strings (`data:font/woff2;base64...`). Because `style.css` is a render-blocking stylesheet, the browser must download and parse all 1.23 MB of CSS before rendering *any* text, which severely delays FCP.
3. **Runtime Tailwind Play CDN**: The site loads `<script src="https://cdn.tailwindcss.com"></script>` directly in the head. This runtime script parses the entire DOM on client-side and compiles Tailwind styles on-the-fly, which is extremely detrimental to rendering performance and Core Web Vitals.
4. **Lucide Icons Script**: Lucide is loaded as an external script in the head, blocking render. We can optimize its load behavior.

---

## Proposed Technical Changes

### Component 1: Critical CSS & Font Optimization
- **File**: `style.css`
- **Action**: Extract the embedded base64 fonts from `style.css` and replace them with optimized, preconnected Google Fonts or standard self-hosted WOFF2 files loaded asynchronously.
- **Result**: Reduce the blocking CSS size from 1.23 MB down to a few kilobytes (or compress/minify it), dramatically speeding up FCP.

### Component 2: Tailwind Compilation & Optimization
- **File**: `includes/header.php` and Tailwind setup
- **Action**: Replace the runtime compilation `<script src="https://cdn.tailwindcss.com"></script>` with a precompiled or optimized build of the Tailwind CSS stylesheet, or load a minified static version of Tailwind.
- **Result**: Eliminate the client-side DOM parsing and style compiling overhead, immediately improving LCP and rendering speed.

### Component 3: Render-Blocking Resources Mitigation
- **File**: `includes/header.php`
- **Action**: Use `defer` or `async` for Lucide icons script and add resource preconnections (`dns-prefetch` and `preconnect`) for external resources (like fonts).
- **Result**: Clear the critical rendering path to allow the browser to display content immediately.

---

## Agent Assignments & Task Breakdown

### Phase 1: Research & Setup
- [ ] Run a local audit without free-hosting challenge redirects to get pure core web vitals.
- [ ] Inspect the Tailwind classes used in the project.

### Phase 2: CSS & Font Decoupling (P0)
- [ ] Modify `style.css` to remove heavy base64 `@font-face` definitions.
- [ ] Update `includes/header.php` to load "Inter" font using standard preconnected Google Fonts link or asynchronous `@font-face`.
- [ ] Compress/minify the remaining styles in `style.css`.

### Phase 3: Tailwind Production Optimization (P0)
- [ ] Compile the used Tailwind CSS classes or replace the Play CDN with a highly performant CSS bundle.
- [ ] Optimize the Lucide script loading (defer/async).

### Phase 4: Verification & Performance Auditing
- [ ] Run the Lighthouse MCP audit for both mobile and desktop on the updated site.
- [ ] Verify that scores are above 90.

---

## Verification Plan

### Automated Tests
- Run Lighthouse audits using the Lighthouse MCP:
  `run_audit(url="https://km-bud.infinityfree.me/?i=1", device="mobile")`
  `run_audit(url="https://km-bud.infinityfree.me/?i=1", device="desktop")`

### Manual Verification
- Verify the site rendering looks beautiful and correct.
- Ensure all dynamic elements (Lucide icons, carousels, maps button) function properly.
