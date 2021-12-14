import {automatedReadability} from 'https://unpkg.com/automated-readability@2.0.0/index.js'

// score for https://sf.gov/information/masks-and-face-coverings-added-protection-coronavirus
// based on hemingway app sentence, word, and character count
const hemscore = automatedReadability({
  sentence: 101,
  word: 1063,
  character: 6189
})
console.log(hemscore)

var e = 5066
var t = 1063
var n = 101
var r = Math.round(4.71 * (e / t) + 0.5 * (t / n) - 21.43);
console.log(r)

const text = document.querySelector('main #block-sfgovpl-content').innerText
console.log(text)

// get sentences
const sentences = text.split(/\.|\?|\!|\n/)
let sentencesData = []
let sentenceCount = 0
let totalWordCount = 0
let totalCharCount = 0
let totalLetterCount = 0

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
    let letterCount = sentence.replace(/\s|,|/g, '').length
    
    totalWordCount += wordCount
    totalCharCount += charCount
    totalLetterCount += letterCount
    sentenceCount++

    let obj = {
      sentence: sentence,
      wordCount: wordCount,
      charCount: charCount,
      letterCount: letterCount,
      score: automatedReadability({
        sentence: 1,
        word: wordCount,
        character: letterCount
      })
    }

    sentencesData.push(obj)
  }
}

console.log('sentences: ' + sentenceCount)
console.log('words: ' + totalWordCount)
console.log('characters: ' + totalCharCount)
console.log('letters: ' + totalLetterCount)

const score = automatedReadability({
  sentence: sentenceCount,
  word: totalWordCount,
  character: totalLetterCount
})

console.log('score: ' + score)

// console.log(sentencesData)

const rootNode = document.querySelector('main #block-sfgovpl-content')
const treeWalker = document.createTreeWalker(rootNode, NodeFilter.SHOW_ELEMENT)
const nodeList = []
let currentNode = treeWalker.currentNode

while(currentNode) {
  nodeList.push(currentNode)
  currentNode = treeWalker.nextNode()
}

console.log(nodeList)

for(let i=0; i<nodeList.length; i++) {
  let node = nodeList[i]
  node.setAttribute('data-index', i)
  for(let j=0; j<node.childNodes.length; j++) {
    if(node.childNodes[j].nodeType == 3) {
      console.log(node.childNodes[j].textContent)
    }
  }
  // console.log(node.innerText)
}
