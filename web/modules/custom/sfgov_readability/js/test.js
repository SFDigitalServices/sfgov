// import {parse} from 'https://unpkg.com/node-html-parser@5.1.0/dist/index.js'
import {automatedReadability} from 'https://unpkg.com/automated-readability@2.0.0/index.js'
import {split} from 'https://unpkg.com/sentence-splitter@3.2.2/lib/sentence-splitter.js'

const score = automatedReadability({
  sentence: 6,
  word: 151,
  character: 623
})

const html = document.querySelector('main #block-sfgovpl-content')[0].innerText




console.log(score)
