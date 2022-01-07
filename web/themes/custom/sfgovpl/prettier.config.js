module.exports = {
  // We have some files with CRLF line endings, which Prettier treats as a violation.
  // The fix is to run, say, dos2unix on those files, remove this setting, then re-run
  // Prettier and make sure that it doesn't flag them as invalid.
  endOfLine: 'auto'
}
