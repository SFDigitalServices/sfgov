import {automatedReadability} from 'https://unpkg.com/automated-readability@2.0.0/index.js'

// score for https://sf.gov/information/masks-and-face-coverings-added-protection-coronavirus
// based on hemingway app sentence, word, and character count
const hemscore = automatedReadability({
  sentence: 101,
  word: 1063,
  character: 6189
})
console.log(hemscore)

const text = document.querySelector('main #block-sfgovpl-content').innerText

// get sentences
const sentences = text.split(/\.|\?|\!|\n/)
let sentencesData = [];
let sentenceCount = 0
let totalWordCount = 0
let totalCharCount = 0

for (let i=0; i<sentences.length; i++) {
  let sentence = sentences[i].trim()
  // if(sentence.length > 0) {
  //   let words = sentence.split(' ')
  //   if(words.length == 1) { // single words don't count as full sentences?
  //     wordCount += 1
  //     charCount += words[0].length
  //   } else {
  //     sentenceCount++;
  //     wordCount += words.length
  //     charCount += sentence.length
  //   }
  // }

  if(sentence.length > 0) {
    let wordCount = sentence.split(' ').length
    let charCount = sentence.length
    
    totalWordCount += wordCount
    totalCharCount += charCount
    sentenceCount++

    let obj = {
      sentence: sentence,
      wordCount: wordCount,
      charCount: charCount,
      score: automatedReadability({
        sentence: 1,
        word: wordCount,
        character: charCount
      })
    }

    sentencesData.push(obj)
  }
}

console.log('sentences: ' + sentenceCount)
console.log('words: ' + totalWordCount)
console.log('characters: ' + totalCharCount)

const score = automatedReadability({
  sentence: sentenceCount,
  word: totalWordCount,
  character: totalCharCount
})

console.log('score: ' + score)

console.log(sentencesData)
