const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
  corePlugins: {
    preflight: false,
  },
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './app/View/Components/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eff6ff',
          100: '#dbeafe',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        },
        success: {
          50: '#ecfdf5',
          100: '#d1fae5',
          600: '#059669',
          700: '#047857',
        },
        warning: {
          50: '#fffbeb',
          100: '#fef3c7',
          600: '#d97706',
          700: '#b45309',
        },
        danger: {
          50: '#fff1f2',
          100: '#ffe4e6',
          600: '#e11d48',
          700: '#be123c',
        },
      },
      fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
        mono: ['Roboto Mono', ...defaultTheme.fontFamily.mono],
      },
      boxShadow: {
        soft: '0 10px 30px rgba(15, 23, 42, 0.08)',
        lift: '0 16px 40px rgba(37, 99, 235, 0.14)',
      },
      borderRadius: {
        card: '12px',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
};
