import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                page: {
                    DEFAULT: '#F6F8FA', // GitHub Light Page
                    dark: '#0D1117',    // GitHub Dark Page
                },
                surface: {
                    DEFAULT: '#FFFFFF', // GitHub Light Card
                    dark: '#161B22',    // GitHub Dark Card
                },
                primary: {
                    DEFAULT: '#1A365D', // Navy Blue
                    hover: '#112240',   // Navy Dark Hover
                    light: '#E2E8F0',   // Light fill
                    dark: '#4A69A3',    // Navy Light (Dark Mode)
                },
                ink: {
                    DEFAULT: '#1F2328', // GitHub Dark Text
                    light: '#656D76',   // GitHub Muted Text
                    dark: '#E6EDF3',    // GitHub Light Text (Dark Mode)
                },
                ghost: {
                    DEFAULT: '#EAEEF2', // Muted areas (darker than page)
                    hover: '#D0D7DE',   // Borders, hover states
                    dark: '#21262D',    // Dark ghost
                },
                'ghost-dark': '#30363D', // Dark ghost hover/border
                danger: {
                    DEFAULT: '#CF222E', // GitHub Red
                    dark: '#F85149',    // GitHub Dark Red
                },
                warning: {
                    DEFAULT: '#BF8700', // GitHub Yellow
                    dark: '#D29922',    // GitHub Dark Yellow
                },
                success: {
                    DEFAULT: '#1A7F37', // GitHub Green
                    dark: '#3FB950',    // GitHub Dark Green
                }
            }
        },
    },

    plugins: [forms],
};