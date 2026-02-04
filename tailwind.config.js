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
                // --- WARNA LAMA (TETAP ADA) ---
                page: {
                    DEFAULT: '#F1F0EC', // Beige kesayangan
                    dark: '#1A1B1E', 
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    dark: '#25262B', 
                },
                primary: {
                    DEFAULT: '#4157A1', // Royal Blue kesayangan
                    hover: '#344580',
                    light: '#C5CAE4',
                    dark: '#5C73C2',
                },
                ink: {
                    DEFAULT: '#51504A',
                    light: '#9CA3AF',
                    dark: '#E4E4E0',
                },
                
                // --- WARNA BARU (YANG DITAMBAHIN) ---
                // "Ghost" = Abu-abu tipis ala Widelab buat Search Bar / Active State
                ghost: {
                    DEFAULT: '#F3F4F6', // Abu terang (Gray-100)
                    hover: '#E5E7EB',   // Abu agak gelap dikit buat hover (Gray-200)
                    dark: '#27272A',    // Abu gelap buat dark mode (Zinc-800)
                },
                
                // Semantic Colors
                danger: { DEFAULT: '#EB2D07' },
                warning: { DEFAULT: '#D89921' },
                success: { 
                    DEFAULT: '#286137',
                    dark: '#4BCE97',
                }
            }
        },
    },

    plugins: [forms],
};