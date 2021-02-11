window.SFGOV = {
  util: {
    getParam(name) {
      const parts = location.search.substr(1).split('&')
      for (const part of parts) {
        const [key, val] = part.split('=')
        if (key === name) {
          return decodeURIComponent(val)
        }
      }
      return undefined
    }
  }
}
