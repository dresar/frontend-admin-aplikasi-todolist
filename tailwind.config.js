/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./includes/**/*.php",
    "./*.php",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#4e73df',
          dark: '#224abe'
        },
        success: {
          DEFAULT: '#1cc88a',
          dark: '#13855c'
        },
        info: {
          DEFAULT: '#36b9cc',
          dark: '#258391'
        },
        warning: {
          DEFAULT: '#f6c23e',
          dark: '#dda20a'
        },
        danger: {
          DEFAULT: '#e74a3b',
          dark: '#be3c30'
        }
      }
    },
  },
  plugins: [],
}