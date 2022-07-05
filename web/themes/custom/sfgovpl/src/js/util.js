const SFGOV = {}

SFGOV.util = {
  getParam (name) {
    let result = null
    let tmp = []
    location.search
      .substr(1)
      .split('&')
      .forEach(item => {
        tmp = item.split('=')
        if (tmp[0] === name) result = decodeURIComponent(tmp[1])
      })
    return result
  }
}
