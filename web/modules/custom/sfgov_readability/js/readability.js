import {automatedReadability} from 'https://unpkg.com/automated-readability@2.0.0/index.js'

const text = document.querySelector('main #block-sfgovpl-content').innerText

// according to the table at https://en.wikipedia.org/wiki/Automated_readability_index
function getARIGradeLevel(score) {
  if (score === 1) return "Kindergarten"
  if (score === 14) return "College student"
  return score - 1;
}

// get sentences
const sentences = text.split(/\.|\?|\!|\n/)
let sentencesData = []
let sentenceCount = 0
let totalWordCount = 0
let totalCharCount = 0
let totalLetterCount = 0

for (let i=0; i<sentences.length; i++) {
  let sentence = sentences[i].trim()

  if(sentence.length > 0) {
    let wordCount = sentence.split(' ').length
    let charCount = sentence.length
    let letterCount = sentence.replace(/[\s;:-]/g, '').length // some things don't count as letters
    
    totalWordCount += wordCount
    totalCharCount += charCount
    totalLetterCount += letterCount
    sentenceCount++

    let obj = {
      sentence: sentence,
      words: wordCount,
      chars: charCount,
      letters: letterCount,
      score: automatedReadability({
        sentence: 1,
        word: wordCount,
        character: letterCount
      })
    }

    sentencesData.push(obj)
  }
}

const pageData = {
  score: Math.ceil(automatedReadability({
    sentence: sentenceCount,
    word: totalWordCount,
    character: totalLetterCount
  })),
  sentences: sentencesData,
  words: totalWordCount,
  chars: totalCharCount,
  letters: totalLetterCount,
}

console.log(pageData)

const gradeElem = document.createElement('div')
gradeElem.setAttribute('id', '#gradeStats')
gradeElem.style.boxShadow = '-1px 1px 5px #666'
gradeElem.classList.add('fixed', 'top-1/3', 'right-0', 'bg-white', 'p-20', 'rounded-l')
gradeElem.innerHTML = '' +
  // '<a class="block no-underline bg-white absolute -top-28 -left-28 text-title-xl" style="border-radius: 35px; padding: 8px 19px; box-shadow: 1px 1px 3px #666" href="#">></a>' + 
  '<p class="p-0 mt-0 mb-20 font-medium text-big-desc">Readability</p>' + 
  '<p class="p-0 mt-0 mb-20 font-medium text-title-md">Grade ' + getARIGradeLevel(pageData.score) + '</p>' +
  '<ul class="p-0 m-0 list-none">' + 
  '  <li class="mb-8"><a href="https://en.wikipedia.org/wiki/Automated_readability_index">ARI</a>: <strong class="font-medium">' + pageData.score + '</strong></li>' +
  '  <li class="mb-8">letters: <strong class="font-medium">' + pageData.letters + '</strong></li>' +
  '  <li class="mb-8">characters: <strong class="font-medium">' + pageData.chars + '</strong></li>' +
  '  <li class="mb-8">words: <strong class="font-medium">' + pageData.words + '</strong></li>' +
  '  <li class="mb-8">sentences: <strong class="font-medium">' + pageData.sentences.length + '</strong></li>' +
  '</ul>'
document.body.append(gradeElem)

// const rootNode = document.querySelector('main #block-sfgovpl-content')
// const links = rootNode.querySelectorAll('a')
// for(let i = 0; i<links.length; i++) {
//   let linkText = links[i].innerText
//   links[i].after(linkText)
//   links[i].remove()
// }
// // console.log(rootNode.querySelectorAll('a').length);
// const treeWalker = document.createTreeWalker(
//   rootNode,
//   NodeFilter.SHOW_TEXT,
//   {
//     acceptNode: (node) => {
//       if (node.nodeType !== 8) { // no comment nodes
//         let isEmptyTextNode = node.data.replace(/\n/g, '').trim().length === 0
//         if (isEmptyTextNode) return NodeFilter.FILTER_REJECT
//         return NodeFilter.FILTER_ACCEPT
//       }
//     }
//   }
// )
// const nodeList = []
// let currentNode = treeWalker.currentNode

// while(currentNode) {
//   nodeList.push(currentNode)
//   currentNode = treeWalker.nextNode()
// }

// console.log(nodeList)

// for(let i=0; i<nodeList.length; i++) {
//   let node = nodeList[i]
//   let parentNode = node.parentNode;
//   parentNode.setAttribute('data-index', i)
//   if(node.nodeType === 3) {
//     console.log(i + ':' + node.data)
//   }
// }
