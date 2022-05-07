(() => {
  const sandboxUsers = drupalSettings.sfgov_sandbox_user.users
  const sandboxPw = drupalSettings.sfgov_sandbox_user.pw
  const selector = '.sfgov-user__content'
  const loginContainer = document.createElement('details')

  loginContainer.style.width = '80%'
  loginContainer.innerHTML = '<summary class="details__summary">Log in as a test user</summary>'
  let html = '<ul class="details__content" style="margin: 0">'
  
  for (let user of sandboxUsers) {
    html += '<li style="margin:0 0 10px 25px"><a data-user="' + user + '" href="">' + user + '</a></li>'
  }

  html += '</ul>'

  loginContainer.innerHTML += html

  document.querySelector(selector).prepend(loginContainer)

  loginContainer.querySelectorAll('a').forEach((item) => {
    item.addEventListener('click', (e) => {
      e.preventDefault()
      document.querySelector('#edit-name').value = item.getAttribute('data-user')
      document.querySelector('#edit-pass').value = sandboxPw
      setTimeout(() => { document.querySelector('#user-login-form').submit() }, 500)
    })
  })
})()
