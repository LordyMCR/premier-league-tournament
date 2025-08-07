import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    // Safelist critical dynamic classes for difficulty badges
    safelist: [
        { pattern: /bg-red-(600|800)/ },
        'text-white',
        'font-bold',
        { pattern: /shadow-(lg|md)/ },
        { pattern: /bg-green-(500|700)/ },
    ],

    theme: {
        extend: {
            screens: {
                'xs': '475px',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [],
};
