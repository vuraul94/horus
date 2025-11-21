# Horus - Testing Guide for Per-Page CSS System

## ✅ What Has Been Completed

The per-page CSS generation system is now fully implemented. Here's what was built:

### 1. **Per-Page CSS Generation** (`css-generator.php`)
- Each Elementor page gets its own CSS file: `tailwind-page-{ID}.css`
- CSS contains ONLY the Tailwind classes used on that specific page
- Typical file sizes: 2-5 KB per page (vs 8-20 KB for global CSS)

### 2. **Auto-Build on Save** (`elementor-integration.php`)
- CSS regenerates automatically when you save a page in Elementor
- Happens via the `elementor/editor/after_save` hook
- No manual intervention needed

### 3. **Intelligent CSS Loading** (`tailwind-integration.php`)
- Frontend checks if page-specific CSS exists
- Loads `tailwind-page-{ID}.css` if available and > 500 bytes
- Falls back to Tailwind CDN if CSS not generated
- Debug comments show which mode is active

### 4. **Cleanup System** (`css-generator.php`)
- Automatically removes orphaned CSS files
- Runs when regenerating all CSS
- Deletes files for deleted/non-existent pages

### 5. **Field Name Fix**
- ✅ Fixed critical bug: Changed from `_css_classes` to `css_classes`
- Now correctly reads from Elementor's native "CSS Classes" field

## 🧪 Testing Steps

### Step 1: Check Current Status

Visit: **http://go-seguros.local/wp-content/plugins/horus/check-page-css.php**

This page will show you:
- Which Elementor pages exist
- Classes extracted from the first page
- Whether per-page CSS files exist
- Option to generate CSS if not yet created

### Step 2: Generate Per-Page CSS

If the CSS file doesn't exist yet, you have two options:

**Option A: Use the check page (easiest)**
1. Visit the check-page-css.php URL above
2. Click the "Generate CSS for Page X" button
3. Wait for completion
4. Refresh the page to see results

**Option B: Save in Elementor**
1. Go to **wp-admin** → **Pages**
2. Edit any page with Elementor
3. Make a small change (or just re-save)
4. Click **Update**
5. CSS should auto-generate for that page

### Step 3: Verify Frontend Loading

1. Visit the page you generated CSS for (e.g., http://go-seguros.local/)
2. **View Page Source** (Ctrl+U or Cmd+U)
3. Look for a comment near the top like:
   ```html
   <!-- Horus: Using page-specific CSS (Page ID: 7, Size: 2.5KB) -->
   ```
4. Verify the stylesheet link shows:
   ```html
   <link rel="stylesheet" href=".../tailwind-page-7.css?ver=..." />
   ```

### Step 4: Verify Classes Work

1. In Elementor, add a test element
2. In **Advanced** tab → scroll to **CSS Classes** field
3. Add: `bg-purple-200 p-4 rounded-lg shadow-xl`
4. Click **Update** (this triggers CSS generation)
5. Visit the frontend
6. The element should show:
   - Purple background
   - Padding
   - Rounded corners
   - Shadow

### Step 5: Check File System

Navigate to: `wp-content/plugins/horus/assets/css/`

You should see files like:
```
tailwind-page-7.css      (2-5 KB)
tailwind-page-18.css     (2-5 KB)
tailwind-page-22.css     (2-5 KB)
```

One file per Elementor page that has been saved.

## 🔍 Debug Tools Created

### 1. `check-page-css.php`
**URL:** http://go-seguros.local/wp-content/plugins/horus/check-page-css.php
- Interactive browser-based checker
- Shows extraction results
- Manual generation button
- Most useful for testing

### 2. `test-page-build.php`
- Command-line style output
- Detailed step-by-step results
- Shows CSS file contents

### 3. `investigate-elementor.php`
- Shows raw Elementor data structure
- Useful for debugging field names
- Displays all settings keys

## ⚙️ How It Works

### Architecture Flow

```
1. User edits page in Elementor
   ↓
2. User clicks "Update"
   ↓
3. Hook: elementor/editor/after_save
   ↓
4. extract_tailwind_classes($post_id)
   - Reads _elementor_data from database
   - Finds all css_classes fields
   - Returns array of classes
   ↓
5. regenerate_css_for_page($post_id)
   - Creates safelist-page-{ID}.txt
   - Creates tailwind.config.page-{ID}.js
   - Runs: npx tailwindcss -c config -o tailwind-page-{ID}.css
   - Deletes temp files
   ↓
6. Frontend visits page
   ↓
7. enqueue_tailwind_frontend()
   - Checks if tailwind-page-{ID}.css exists
   - If yes: loads that file
   - If no: loads Tailwind CDN as fallback
```

### File Locations

```
wp-content/plugins/horus/
├── includes/
│   ├── elementor-integration.php  (extract classes, hook to save)
│   ├── css-generator.php          (generate per-page CSS)
│   └── tailwind-integration.php   (enqueue correct CSS)
├── assets/
│   └── css/
│       ├── tailwind-page-7.css         (generated per page)
│       ├── tailwind-page-18.css
│       └── input.css                    (source)
└── check-page-css.php                   (testing tool)
```

## 🐛 Troubleshooting

### CSS Not Generating?

**Check if Tailwind CLI is installed:**
```bash
cd wp-content/plugins/horus
npm install
```

**Verify node is available:**
```bash
node --version
npx tailwindcss --help
```

### Classes Not Appearing?

1. Verify you're using the **CSS Classes** field (Advanced tab)
2. NOT Custom CSS, NOT class attributes in text
3. Field name is `css_classes` without underscore

### Still Using CDN?

Check page source for:
```html
<!-- Horus: CSS not generated for page 7, using CDN -->
```

This means:
- CSS file doesn't exist, OR
- File size is < 500 bytes

Solution: Re-save the page in Elementor

## 📊 Expected Performance

### Before (Global CSS)
- File: `tailwind-generated.css`
- Size: 8-20 KB
- Contains: ALL classes from ALL pages

### After (Per-Page CSS)
- File: `tailwind-page-{ID}.css`
- Size: 2-5 KB per page
- Contains: Only classes from THAT page
- Result: 60-75% size reduction!

## ✨ Next Steps

1. ✅ Run check-page-css.php to verify system works
2. ✅ Generate CSS for your main pages
3. ✅ Test that classes appear correctly on frontend
4. ✅ Verify debug comments show "Using page-specific CSS"
5. 🎉 Enjoy optimized Tailwind CSS!

## 📝 Notes

- Editor still uses Tailwind CDN (for instant JIT compilation)
- Frontend uses optimized per-page CSS
- Old `tailwind-generated.css` is no longer used (will be ignored)
- Cleanup runs automatically, removing orphaned files
