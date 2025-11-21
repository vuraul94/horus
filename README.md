# Horus - Tailwind CSS for Elementor

**Integrates Tailwind CSS with Elementor, enabling real-time Tailwind classes in the editor with JIT compilation and automatic purging.**

## Features

- ✨ **Real-time Tailwind classes in Elementor editor** - See your Tailwind styles instantly as you type
- ⚡ **JIT (Just-In-Time) compilation** - Only generates CSS for classes you actually use
- 🎨 **Full Tailwind v3 support** - Access all Tailwind utilities, including latest features
- 📱 **Responsive controls** - Separate fields for mobile, tablet, and desktop classes
- 🔧 **Automatic CSS optimization** - Purges unused CSS for optimal performance
- 🚀 **CDN fallback** - Uses Tailwind Play CDN in editor for instant rendering
- 💾 **Optimized frontend** - Serves minified, purged CSS on production
- 🎯 **Zero configuration** - Works out of the box, advanced options available
- 🎛️ **Grid Areas plugin included** - Use named grid areas for easier layout management

## Installation

1. Upload the `horus` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure Elementor is installed and activated (required)
4. Start using Tailwind classes in Elementor!

## Usage

### Basic Usage

1. Edit any page with Elementor
2. Select any widget
3. Go to the **Advanced** tab
4. Find the **Tailwind CSS (Horus)** section
5. Enter Tailwind classes in the "Tailwind Classes" field
6. See your styles applied in real-time!

### Example Classes

```
bg-blue-500 text-white p-4 rounded-lg shadow-xl hover:bg-blue-600 transition
```

### Responsive Design

Use the separate fields for different breakpoints:

- **Tailwind Classes**: Base classes (apply to all sizes)
- **Mobile Classes**: Classes with `sm:` prefix
- **Tablet Classes**: Classes with `md:` prefix
- **Desktop Classes**: Classes with `lg:` prefix

Example:
```
Base: bg-gray-100 p-2
Mobile: sm:p-3 sm:text-sm
Tablet: md:p-4 md:text-base
Desktop: lg:p-6 lg:text-lg
```

### Grid Areas

Horus includes the Grid Areas plugin for easier CSS Grid layouts with named areas:

```
grid grid-areas-layout grid-rows-layout grid-cols-layout
```

Define your layout in the parent element:
```
grid grid-cols-3 grid-rows-3
grid-areas-[header,header,header]
grid-areas-[sidebar,main,main]
grid-areas-[footer,footer,footer]
```

Then assign children to areas:
```
grid-in-[header]
grid-in-[sidebar]
grid-in-[main]
grid-in-[footer]
```

**Complete Example:**

Parent container:
```
grid grid-cols-[200px_1fr] grid-rows-[auto_1fr_auto] gap-4
grid-areas-[header,header]
grid-areas-[sidebar,main]
grid-areas-[footer,footer]
```

Children:
- Header: `grid-in-[header] bg-blue-500`
- Sidebar: `grid-in-[sidebar] bg-gray-200`
- Main: `grid-in-[main] bg-white`
- Footer: `grid-in-[footer] bg-gray-800`

## How It Works

### In Elementor Editor
- Uses **Tailwind Play CDN** with built-in JIT
- All Tailwind classes work instantly
- No build process needed
- Perfect for rapid development

### On Frontend
- Serves **optimized, purged CSS**
- Contains only the classes you actually use
- Minified for performance
- Falls back to CDN if no generated CSS exists

### CSS Generation

The plugin automatically:
1. Scans all Elementor pages for Tailwind classes
2. Generates optimized CSS containing only those classes
3. Updates CSS when you save Elementor pages
4. Caches for optimal performance

## Advanced Usage

### Manual CSS Regeneration

Go to **Elementor > Tailwind CSS** in WordPress admin to:
- View generation status
- Manually trigger CSS regeneration
- See last generation time

### CLI Build Process (Optional)

For optimal performance, use Tailwind CLI:

```bash
# Navigate to plugin directory
cd wp-content/plugins/horus

# Install dependencies
npm install

# Generate optimized CSS
npm run build

# Watch for changes (development)
npm run watch
```

### Custom Tailwind Configuration

Edit `tailwind.config.js` to customize:
- Colors
- Fonts
- Spacing
- Breakpoints
- And more!

Example:
```js
theme: {
  extend: {
    colors: {
      'brand': '#7c3aed',
    },
  },
}
```

### Hooks & Filters

**Filter Tailwind config:**
```php
add_filter('horus_tailwind_config', function($config) {
    $config['theme']['extend']['colors']['custom'] = '#ff0000';
    return $config;
});
```

## Requirements

- WordPress 5.9 or higher
- PHP 7.4 or higher
- Elementor 3.0 or higher

## Optional Requirements

For CLI build process:
- Node.js 14 or higher
- npm or yarn

## Browser Support

Supports all modern browsers:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Performance

### Editor
- Lightweight CDN load (~40KB compressed)
- JIT compilation
- No build step required

### Frontend
- Minified CSS (typically 5-20KB)
- Only includes used classes
- Cached and optimized
- Fast page loads

## Troubleshooting

### Classes not appearing in editor
- Refresh the Elementor editor
- Clear browser cache
- Check browser console for errors

### Classes not appearing on frontend
- Regenerate CSS in plugin settings
- Check file permissions on `/assets/css/` folder
- Verify plugin is activated

### Conflicts with other CSS
- Tailwind uses low specificity
- May need to use `!important` in some cases
- Or increase specificity with custom classes

## FAQ

**Q: Do I need to install Node.js?**
A: No! The plugin works out of the box with CDN. Node.js is optional for advanced optimization.

**Q: Will this slow down my site?**
A: No! The frontend uses optimized CSS containing only the classes you use.

**Q: Can I use custom Tailwind plugins?**
A: Yes, if you use the CLI build process. Add plugins to `tailwind.config.js`.

**Q: Does it work with Elementor Pro?**
A: Yes! Works with both free and Pro versions.

**Q: Can I use arbitrary values like `bg-[#1da1f2]`?**
A: Yes! All Tailwind features are supported.

## Roadmap

- [ ] Visual Tailwind class picker
- [ ] Preset class combinations
- [ ] Copy/paste classes between elements
- [ ] Export/import color palettes
- [ ] Integration with Elementor Theme Builder
- [ ] Custom utility class builder

## Support

For issues, questions, or suggestions:
- Create an issue on GitHub
- Contact support

## Credits

- **Tailwind CSS** - https://tailwindcss.com
- **Elementor** - https://elementor.com

## License

GPL v2 or later

---

**Made with ❤️ for the WordPress & Elementor community**
