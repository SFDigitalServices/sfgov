const toggles = document.querySelectorAll('[data-toggle-container]')

for (const toggle of toggles) {
  const trigger = toggle.querySelector('[data-toggle-trigger]')

  if (!trigger) {
    console.error('Toggle element contains no trigger (with the "data-toggle-trigger" attribute)', toggle)
    continue
  }

  trigger.addEventListener('click', e => {
    const triggerLink = e.target
    const showMoreText = toggle.getAttribute('data-show-text') || 'Show more'
    const showLessText = toggle.getAttribute('data-hide-text') || 'Show less'
    if (toggle.hasAttribute('data-toggle-show')) {
      toggle.removeAttribute('data-toggle-show')
      triggerLink.innerHTML = showMoreText
    } else {
      toggle.setAttribute('data-toggle-show', 'true')
      triggerLink.innerHTML = showLessText
    }
  })
}
