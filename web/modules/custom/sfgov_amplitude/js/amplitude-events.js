'use strict'

document.addEventListener('DOMContentLoaded', () => {
  
  document.addEventListener('click', (event) => {
    let target = event.target
    const vaccineFilterForm = document.querySelector('#vaccine-filter-form')
    
    if (vaccineFilterForm) {
      const vaccineFilterApplyButton = vaccineFilterForm.querySelector('#edit-submit')

      if (target === vaccineFilterApplyButton) {
        const filterChecks = vaccineFilterForm.querySelectorAll('input:checked')
        const filterSelects = vaccineFilterForm.querySelectorAll('select')
        const filters = []
        
        for (let i = 0; i < filterChecks.length; i++) {
          filters.push(filterChecks[i].getAttribute('name'))
        }
  
        for(let i = 0; i < filterSelects.length; i++) {
          const filterSelect = filterSelects[i]
          filters.push(filterSelect.value + '|' + filterSelect.options[filterSelect.selectedIndex].text)
        }
  
        amplitude.getInstance().logEvent('vaccine-sites-filter-click', { filters })
      } 
    }
  })

});
