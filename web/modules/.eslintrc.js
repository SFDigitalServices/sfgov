/**
 * This file short-circuits ESLint's configuration discovery
 * so that it doesn't look for configs in the web/core directory.
 */

/** @type {import('eslint').ESLint.ConfigData} */
module.exports = {
  extends: '../../.eslintrc'
}