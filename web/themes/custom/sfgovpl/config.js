"use strict";

module.exports = {
  css: {
    source: './src/sass/**/*.scss',
    dest: './dist/css'
  },
  images: {
    source: './src/img/**',
    dest: './dist/img',
  },
  autoprefixer: [
    'last 2 versions',
    '> 1%',
    'ie 11'
  ]
};
