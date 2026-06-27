/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          rose: '#DE388D',
          'rose-dark': '#C0267A',
          'rose-light': '#FDE8F4',
          vert: '#A7C93F',
          'vert-dark': '#7A9D24',
          'vert-light': '#F3F9E6',
          bleu: '#50AFAF',
          'bleu-dark': '#3A8E8E',
          'bleu-light': '#EBF7F7',
          'bleu-fonce': '#41758B',
          'bleu-fonce-dark': '#2E5D70',
          'bleu-fonce-light': '#EDF4F7',
        },
      },
    },
  },
  plugins: [],
}
