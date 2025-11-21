const express = require('express');
const cors = require('cors');
const postcss = require('postcss');
const tailwindcss = require('tailwindcss');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3200;
const BASE_PATH = process.env.BASE_PATH || '';

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));

// Create router for API endpoints
const router = express.Router();

// Health check endpoint
router.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        service: 'horus-tailwind-api',
        version: '1.0.0'
    });
});

// Ping endpoint (simple check if API is up)
router.get('/ping', (req, res) => {
    res.send('pong');
});

// Main endpoint to compile Tailwind CSS
router.post('/compile', async (req, res) => {
    try {
        const { classes, config } = req.body;

        if (!classes || !Array.isArray(classes)) {
            return res.status(400).json({
                error: 'Invalid request: classes array is required'
            });
        }

        console.log(`[Horus API] Compiling ${classes.length} classes...`);

        // Create Tailwind config with safelist
        const tailwindConfig = {
            content: [{ raw: '', extension: 'html' }],
            safelist: classes,
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            200: '#f5d0fe',
                            300: '#f0abfc',
                            400: '#e879f9',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                            800: '#86198f',
                            900: '#701a75',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Lexend', 'sans-serif'],
                    },
                    spacing: {
                        '128': '32rem',
                        '144': '36rem',
                    },
                    borderRadius: {
                        '4xl': '2rem',
                    },
                    ...(config?.theme?.extend || {}),
                },
            },
            plugins: [
                require('@savvywombat/tailwindcss-grid-areas'),
            ],
            important: '.elementor',
        };

        // Merge custom config if provided
        if (config?.colors) {
            tailwindConfig.theme.extend.colors = {
                ...tailwindConfig.theme.extend.colors,
                ...config.colors
            };
        }

        // Input CSS with Tailwind directives
        const inputCSS = `
@tailwind base;
@tailwind components;
@tailwind utilities;
        `.trim();

        // Process with PostCSS and Tailwind
        const result = await postcss([
            tailwindcss(tailwindConfig),
        ]).process(inputCSS, {
            from: undefined
        });

        // Minify the output (simple minification)
        const minifiedCSS = result.css
            .replace(/\/\*[\s\S]*?\*\//g, '') // Remove comments
            .replace(/\s+/g, ' ')              // Collapse whitespace
            .replace(/\s*{\s*/g, '{')          // Remove space around {
            .replace(/\s*}\s*/g, '}')          // Remove space around }
            .replace(/\s*;\s*/g, ';')          // Remove space around ;
            .replace(/\s*:\s*/g, ':')          // Remove space around :
            .replace(/\s*,\s*/g, ',')          // Remove space around ,
            .trim();

        console.log(`[Horus API] Compiled successfully. Output: ${minifiedCSS.length} bytes`);

        res.json({
            success: true,
            css: minifiedCSS,
            stats: {
                classesReceived: classes.length,
                outputSize: minifiedCSS.length
            }
        });

    } catch (error) {
        console.error('[Horus API] Compilation error:', error);
        res.status(500).json({
            error: 'Compilation failed',
            message: error.message
        });
    }
});

// Mount router at root and with BASE_PATH prefix
app.use('/', router);
if (BASE_PATH) {
    app.use(BASE_PATH, router);
}
// Also mount at /horus-api by default for Namecheap-style hosting
app.use('/horus-api', router);

// Start server
app.listen(PORT, () => {
    console.log(`[Horus API] Tailwind compilation server running on port ${PORT}`);
    console.log(`[Horus API] BASE_PATH: ${BASE_PATH || '(none)'}`);
    console.log(`[Horus API] Available routes:`);
    console.log(`  - /ping, /health, /compile`);
    console.log(`  - /horus-api/ping, /horus-api/health, /horus-api/compile`);
    if (BASE_PATH) {
        console.log(`  - ${BASE_PATH}/ping, ${BASE_PATH}/health, ${BASE_PATH}/compile`);
    }
});
