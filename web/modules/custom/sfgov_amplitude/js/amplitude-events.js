/* global amplitude */
document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('click', event => {
    const target = event.target
    const vaccineFilterForm = document.querySelector('#vaccine-filter-form')

    if (vaccineFilterForm) {
      const vaccineFilterApplyButton = vaccineFilterForm.querySelector('#edit-submit')

      if (target === vaccineFilterApplyButton) {
        const filterChecks = vaccineFilterForm.querySelectorAll('input:checked')
        const filterSelects = vaccineFilterForm.querySelectorAll('select')
        const filters = []

        for (let i = 0; i < filterChecks.length; i++) {
          const labelId = filterChecks[i].getAttribute('id')
          const labelText = vaccineFilterForm.querySelector('label[for="' + labelId + '"]').innerText

          filters.push(labelText)
        }

        for (let i = 0; i < filterSelects.length; i++) {
          const filterSelect = filterSelects[i]
          const filterSelectText = filterSelect.options[filterSelect.selectedIndex].text

          filters.push(filterSelectText)
        }

        amplitude.getInstance().logEvent('Vaccine sites filter click', { filters })
      }
    }
  })
})
