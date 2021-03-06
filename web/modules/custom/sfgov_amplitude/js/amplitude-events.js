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
          let labelId = filterChecks[i].getAttribute('id')
          let labelText = vaccineFilterForm.querySelector('label[for="' + labelId + '"]').innerText

          filters.push(labelText)
        }
  
        for(let i = 0; i < filterSelects.length; i++) {
          let filterSelect = filterSelects[i]
          let filterSelectText = filterSelect.options[filterSelect.selectedIndex].text

          filters.push(filterSelectText)
        }
  
        amplitude.getInstance().logEvent('Vaccine sites filter click', { filters })
      } 
    }
  })

});
