# Icon Options for TRMNL NSW Trip Planner

E-ink displays work best with **high contrast, solid filled shapes** rather than thin strokes.
Below are multiple icon sets you can copy into `nsw-trip.blade.php`.

---

## Option 1: Filled Solid Shapes (Recommended for E-Ink)

Best contrast and visibility. Black silhouettes on white background.

### Train (front view)
```svg
<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="4" y="2" width="16" height="14" rx="3"/><rect x="6" y="5" width="4" height="3" rx="1"/><rect x="14" y="5" width="4" height="3" rx="1"/><rect x="10" y="12" width="4" height="2" rx="1"/><path d="M2 16h20v2H2z"/><circle cx="7" cy="21" r="2"/><circle cx="17" cy="21" r="2"/></svg>
```

### Bus (front view)
```svg
<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="3" y="3" width="18" height="14" rx="2"/><rect x="6" y="6" width="4" height="3" rx="1"/><rect x="14" y="6" width="4" height="3" rx="1"/><rect x="10" y="12" width="4" height="2" rx="1"/><circle cx="7" cy="21" r="2"/><circle cx="17" cy="21" r="2"/></svg>
```

---

## Option 2: Outline Style (Current Default)

Uses `stroke` instead of `fill`. Looks more refined but thinner on e-ink.

### Train
```svg
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l2 3v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6l2-3z"/><path d="M6 11h12"/><path d="M6 16h12"/><path d="M8 21v2"/><path d="M16 21v2"/></svg>
```

### Bus
```svg
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M6 18v2"/><path d="M18 18v2"/><path d="M6 9h12"/></svg>
```

---

## Option 3: Simple Geometric (Ultra Minimal)

Just basic shapes. Maximum readability at tiny sizes.

### Train
```svg
<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="5" y="2" width="14" height="16" rx="2"/><rect x="8" y="6" width="8" height="4" rx="1"/><rect x="9" y="14" width="6" height="3" rx="1"/></svg>
```

### Bus
```svg
<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="4" y="2" width="16" height="16" rx="2"/><rect x="7" y="6" width="10" height="5" rx="1"/><rect x="9" y="15" width="6" height="3" rx="1"/></svg>
```

---

## Option 4: Unicode Symbols (No SVG)

If SVG icons don't render well, use Unicode characters. These are guaranteed to work but less visual.

- Train: `&#x1F683;` 🚆 or just `T`
- Bus: `&#x1F68C;` 🚌 or just `B`
- Metro: `M`
- Light Rail: `L`
- Ferry: `F`

---

## Option 5: Letter in a Box (Badge Style)

Clear and unambiguous. Each mode letter inside a small rounded rectangle.

### Train Badge
```svg
<svg width="20" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="2" y="4" width="20" height="16" rx="3"/><text x="12" y="16" font-family="sans-serif" font-size="11" font-weight="bold" fill="white" text-anchor="middle">T</text></svg>
```

### Bus Badge
```svg
<svg width="20" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="2" y="4" width="20" height="16" rx="3"/><text x="12" y="16" font-family="sans-serif" font-size="11" font-weight="bold" fill="white" text-anchor="middle">B</text></svg>
```

---

## Recommendation

For TRMNL e-ink displays (800x480, 1-bit black & white):
- **Best overall:** Option 1 (Filled Solid Shapes)
- **If SVG doesn't render:** Option 4 (Unicode) or Option 5 (Badge)
- **Avoid:** Thin stroke widths (< 2px) as they disappear on e-ink

## Arrow Options for Route Header

The `&rarr;` HTML entity may not render. Safer alternatives:
- `→` (Unicode arrow)
- `->` (ASCII)
- `>` (simple)
- ` to ` (just the word)
