import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#fbf3e4',
                    100: '#f5e1b8',
                    200: '#efce87',
                    300: '#e8bb56',
                    400: '#edbf02',
                    500: '#e0a400',
                    600: '#d27012',
                    700: '#a85a0f',
                    800: '#5c3a12',
                    900: '#222323',
                },
            },
        },
    },

    plugins: [forms],
};
