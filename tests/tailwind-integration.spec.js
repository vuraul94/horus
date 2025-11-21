const { test, expect } = require('@playwright/test');

test.describe('Horus - Tailwind CSS Integration', () => {

  test('should load Tailwind CSS on frontend', async ({ page }) => {
    // Navigate to homepage
    await page.goto('/');

    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Check if Tailwind CSS is loaded (either generated or CDN)
    const stylesheets = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
      return links.map(link => ({
        href: link.href,
        loaded: true
      }));
    });

    const scripts = await page.evaluate(() => {
      const scriptTags = Array.from(document.querySelectorAll('script'));
      return scriptTags.map(script => ({
        src: script.src,
        loaded: true
      }));
    });

    // Check for Tailwind CSS (generated file or CDN)
    const hasTailwindGenerated = stylesheets.some(s => s.href.includes('tailwind-generated.css'));
    const hasTailwindCDN = scripts.some(s => s.src.includes('cdn.tailwindcss.com'));

    console.log('Stylesheets loaded:', stylesheets.length);
    console.log('Has tailwind-generated.css:', hasTailwindGenerated);
    console.log('Has Tailwind CDN:', hasTailwindCDN);

    // At least one should be true
    expect(hasTailwindGenerated || hasTailwindCDN).toBe(true);
  });

  test('should have Horus debug comments in HTML', async ({ page }) => {
    await page.goto('/');

    const htmlContent = await page.content();

    // Check for Horus debug comments
    const hasHorusDebug = htmlContent.includes('<!-- Horus Debug:') ||
                         htmlContent.includes('<!-- Horus:');

    console.log('Has Horus debug comments:', hasHorusDebug);

    if (hasHorusDebug) {
      // Extract debug info
      const debugMatch = htmlContent.match(/<!-- Horus Debug: (.*?) -->/);
      const modeMatch = htmlContent.match(/<!-- Horus: (.*?) -->/);

      if (debugMatch) console.log('Debug info:', debugMatch[1]);
      if (modeMatch) console.log('Mode:', modeMatch[1]);
    }

    expect(hasHorusDebug).toBe(true);
  });

  test('should apply Tailwind classes to elements with CSS Classes field', async ({ page }) => {
    await page.goto('/');

    // Find elements with common Tailwind classes
    const elementWithBg = await page.locator('[class*="bg-"]').first();
    const elementWithText = await page.locator('[class*="text-"]').first();
    const elementWithPadding = await page.locator('[class*="p-"]').first();

    // Check if any elements have Tailwind classes
    const hasBgClass = await elementWithBg.count() > 0;
    const hasTextClass = await elementWithText.count() > 0;
    const hasPaddingClass = await elementWithPadding.count() > 0;

    console.log('Elements with bg-* class:', hasBgClass);
    console.log('Elements with text-* class:', hasTextClass);
    console.log('Elements with p-* class:', hasPaddingClass);

    // If any Tailwind classes are found, check computed styles
    if (hasBgClass) {
      const bgColor = await elementWithBg.evaluate(el => {
        return window.getComputedStyle(el).backgroundColor;
      });
      console.log('Background color:', bgColor);

      // Should not be transparent/default
      expect(bgColor).not.toBe('rgba(0, 0, 0, 0)');
    }
  });

  test('should scan and detect CSS file size', async ({ page }) => {
    await page.goto('/');

    // Get all loaded resources
    const resources = await page.evaluate(() => {
      const perfEntries = performance.getEntriesByType('resource');
      return perfEntries.map(entry => ({
        name: entry.name,
        size: entry.transferSize,
        type: entry.initiatorType
      }));
    });

    // Find Tailwind-related resources
    const tailwindResources = resources.filter(r =>
      r.name.includes('tailwind') || r.name.includes('cdn.tailwindcss.com')
    );

    console.log('Tailwind resources:', tailwindResources);

    tailwindResources.forEach(resource => {
      console.log(`${resource.name}: ${(resource.size / 1024).toFixed(2)} KB`);
    });

    if (tailwindResources.length > 0) {
      // Check if using optimized CSS (should be < 50KB) or CDN (larger)
      const hasOptimizedCSS = tailwindResources.some(r =>
        r.name.includes('tailwind-generated.css') && r.size < 50000
      );

      console.log('Using optimized CSS:', hasOptimizedCSS);
    }
  });

  test('should extract all Tailwind classes from page', async ({ page }) => {
    await page.goto('/');

    // Extract all classes from the page
    const allClasses = await page.evaluate(() => {
      const elements = document.querySelectorAll('[class]');
      const classSet = new Set();

      elements.forEach(el => {
        const classes = el.className.split(' ');
        classes.forEach(c => {
          if (c.trim()) classSet.add(c.trim());
        });
      });

      return Array.from(classSet);
    });

    // Filter Tailwind-like classes
    const tailwindClasses = allClasses.filter(c =>
      /^(sm:|md:|lg:|xl:|2xl:|hover:|focus:|active:|group-hover:|dark:)?[\w\-\[\]\/]+$/.test(c) &&
      (c.includes('-') || c.includes(':'))
    );

    console.log('Total classes found:', allClasses.length);
    console.log('Tailwind-like classes:', tailwindClasses.length);
    console.log('Sample Tailwind classes:', tailwindClasses.slice(0, 10));

    // Save to file for inspection
    const fs = require('fs');
    const path = require('path');
    fs.writeFileSync(
      path.join(__dirname, 'detected-classes.txt'),
      tailwindClasses.join('\n')
    );

    console.log('Classes saved to tests/detected-classes.txt');
  });
});
