const { test } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

test('Extract classes from HTML and rebuild CSS', async ({ page }) => {
  console.log('1. Loading homepage...');
  await page.goto('/');
  await page.waitForLoadState('networkidle');

  console.log('2. Extracting all classes from HTML...');
  const allClasses = await page.evaluate(() => {
    const elements = document.querySelectorAll('[class]');
    const classSet = new Set();

    elements.forEach(el => {
      // Handle both string and SVGAnimatedString
      let className = el.className;
      if (typeof className === 'object' && className.baseVal !== undefined) {
        className = className.baseVal;
      }

      if (typeof className === 'string') {
        const classes = className.split(' ');
        classes.forEach(c => {
          if (c.trim()) classSet.add(c.trim());
        });
      }
    });

    return Array.from(classSet);
  });

  // Filter Tailwind classes
  const tailwindClasses = allClasses.filter(c =>
    /^(container|sm:|md:|lg:|xl:|2xl:|hover:|focus:|active:|group-hover:|dark:)?[\w\-\[\]\/]+$/.test(c) &&
    (c.includes('-') || c.includes(':') || c.includes('['))
  );

  console.log(`   Total classes: ${allClasses.length}`);
  console.log(`   Tailwind classes: ${tailwindClasses.length}`);

  // Save all Tailwind classes to safelist
  const safelistPath = path.join(__dirname, '../assets/safelist.txt');
  fs.writeFileSync(safelistPath, tailwindClasses.join('\n'));
  console.log(`3. Safelist saved: ${tailwindClasses.length} classes`);

  // Show first 20 classes
  console.log('\n   Sample classes found:');
  tailwindClasses.slice(0, 20).forEach(c => console.log(`   - ${c}`));

  // Build CSS
  console.log('\n4. Building CSS with Tailwind CLI...');
  const pluginPath = path.join(__dirname, '..');

  try {
    const output = execSync('npm run build', {
      cwd: pluginPath,
      encoding: 'utf-8'
    });
    console.log(output);
  } catch (error) {
    console.error('Build error:', error.message);
  }

  // Check result
  const cssPath = path.join(pluginPath, 'assets/css/tailwind-generated.css');
  if (fs.existsSync(cssPath)) {
    const stats = fs.statSync(cssPath);
    const sizeKB = (stats.size / 1024).toFixed(2);
    console.log(`\n✅ CSS rebuilt: ${sizeKB} KB`);
    console.log(`   File: ${cssPath}`);

    // Check if bg-purple-200 is in the CSS
    const cssContent = fs.readFileSync(cssPath, 'utf-8');
    const hasPurple = cssContent.includes('bg-purple-200') || cssContent.includes('.bg-purple-200');
    console.log(`   Contains bg-purple-200: ${hasPurple ? '✅ YES' : '❌ NO'}`);

    if (hasPurple) {
      console.log('\n🎉 SUCCESS! Now refresh http://go-seguros.local/ and you should see the purple background!');
    } else {
      console.log('\n⚠️  bg-purple-200 not found in CSS. Checking if class was detected...');
      const hasInSafelist = tailwindClasses.includes('bg-purple-200');
      console.log(`   In safelist: ${hasInSafelist ? 'YES' : 'NO'}`);
    }
  } else {
    console.log('\n❌ CSS file not generated');
  }
});
