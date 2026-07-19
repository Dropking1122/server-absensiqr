import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Status badge colors
        'bg-green-50', 'text-green-700', 'ring-green-600/20', 'bg-green-500',
        'bg-gray-50',  'text-gray-600',  'ring-gray-500/10',  'bg-gray-400',
        'bg-red-50',   'text-red-700',   'ring-red-600/20',   'bg-red-500',
        'bg-yellow-50','text-yellow-700','ring-yellow-600/20','bg-yellow-400',
        // Release category colors
        'bg-indigo-50','text-indigo-700','ring-indigo-600/20',
        'bg-blue-50',  'text-blue-700',  'ring-blue-600/20',
        'bg-orange-50','text-orange-700','ring-orange-600/20',
        // Priority colors
        'bg-yellow-50','text-yellow-700','ring-yellow-600/20',
        // Progress bars
        'bg-green-500','bg-yellow-400','bg-red-400',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
