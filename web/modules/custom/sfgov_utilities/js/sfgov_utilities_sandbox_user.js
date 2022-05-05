(() => {
  const style = document.createElement('style')
  style.innerHTML = `
    .sfgov-user__login {
      position: relative;
    }

    details.sfgov-sandbox-user {
      position: absolute;
      top: 85px;
      background: #fff;
      margin: 0 0 20px 0;
      border: 3px solid red;
      border-radius: 0;
      padding: 10px;
    }

    details.sfgov-sandbox-user ul {
      padding: 20px;
      margin: 0;
      list-style-type: none;
    }

    details.sfgov-sandbox-user ul li {
      margin-bottom: 10px;
    }

    details.sfgov-sandbox-user summary {
      display: revert;
      font-size: initial;
      font-weight:normal;
      line-height:initial;
      border:0;
      border-radius: 0;
      color:initial;
      padding:0;
    }

    details.sfgov-sandbox-user summary:after {
      display: none;
    }

    details.sfgov-sandbox-user[open] summary {
      background: none;
      border-radius: 0;
    }
  `
  document.head.appendChild(style)

  const sandboxUsers = drupalSettings.sfgov_sandbox_user.users
  const sandboxPw = drupalSettings.sfgov_sandbox_user.pw
  const selector = '.sfgov-user__login h1'
  const loginContainer = document.createElement('details')

  loginContainer.className = 'sfgov-sandbox-user'
  loginContainer.innerHTML = '<summary>Login as a test user</summary>'
  let html = '<ul>'
  
  for (let user of sandboxUsers) {
    html += '<li><a data-user="' + user + '" href="">' + user + '</a></li>'
  }

  html += '</ul>'

  loginContainer.innerHTML += html

  document.querySelector(selector).after(loginContainer)

  loginContainer.querySelectorAll('a').forEach((item) => {
    item.addEventListener('click', (e) => {
      e.preventDefault()
      document.querySelector('#edit-name').value = item.getAttribute('data-user')
      document.querySelector('#edit-pass').value = sandboxPw
      setTimeout(() => { document.querySelector('#user-login-form').submit() }, 500)
    })
  })
})()
