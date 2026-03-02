import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
        './vendor/robsontenorio/mary/src/View/Components/**/*.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        forms,
        typography,
        daisyui,
    ],
    daisyui: {
        themes: [
            {
                'kt-light': {
                    'primary': '#2563eb',
                    'primary-content': '#ffffff',
                    'secondary': '#7c3aed',
                    'accent': '#059669',
                    'neutral': '#374151',
                    'base-100': '#f9fafb',
                    'base-200': '#f3f4f6',
                    'base-300': '#e5e7eb',
                    'base-content': '#111827',
                    'info': '#0ea5e9',
                    'success': '#10b981',
                    'warning': '#f59e0b',
                    'error': '#ef4444',
                },
            },
            {
                'kt-dark': {
                    'primary': '#3b82f6',
                    'primary-content': '#ffffff',
                    'secondary': '#8b5cf6',
                    'accent': '#34d399',
                    'neutral': '#1f2937',
                    'base-100': '#111827',
                    'base-200': '#1f2937',
                    'base-300': '#374151',
                    'base-content': '#f9fafb',
                    'info': '#38bdf8',
                    'success': '#34d399',
                    'warning': '#fbbf24',
                    'error': '#f87171',
                },
            },
        ],
        darkTheme: 'kt-dark',
    },
};
