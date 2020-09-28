try {
  require('dotenv').config()
} catch (error) {
  // this can fail in CI, where there is no .env
}

const {
  LHCI_BUILD_TOKEN: token,
  LHCI_COLLECT_BASE_URL: baseUrl = 'http://sfgov.lndo.site',
} = process.env

module.exports = {
  ci: {
    collect: {
      url: baseUrl,
      staticDistDir: false
    },
    assert: {
    },
    upload: {
      target: 'lhci',
      token,
      ignoreDuplicateBuildFailure: true,
      urlReplacementPatterns: [
        `s#^${baseUrl}##`
      ]
    }
  }
}
