(() => {
  const surveyBtn = document.querySelector('.survey-btn')
  const close = document.createElement('a')
  close.setAttribute('href', 'javascript:void(0)')
  close.innerText = 'close'
  if (surveyBtn) {
    surveyBtn.addEventListener('click', e => {
      e.stopPropagation()
      e.preventDefault()
      const smcxWidget = document.querySelector('.smcx-widget')
      smcxWidget.style.setProperty('display', 'block', 'important')
      smcxWidget.style.setProperty('position', 'fixed', 'important')
      smcxWidget.style.setProperty('top', '40px')
      smcxWidget.style.setProperty('left', 'calc(50% - ' + (smcxWidget.offsetWidth / 2) + 'px)')

      // add close link
      smcxWidget.prepend(close)
      close.addEventListener('click', () => {
        smcxWidget.style.setProperty('display', 'none', 'important')
      })
    })
  }
})()
