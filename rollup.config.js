import { babel } from '@rollup/plugin-babel'
import commonjs from '@rollup/plugin-commonjs'
import json from '@rollup/plugin-json'
import resolve from '@rollup/plugin-node-resolve'

/** @type {import('rollup').RollupOptions} */
const config = {
  plugins: [
    resolve(),
    commonjs(),
    json(),
    babel({
      babelHelpers: 'runtime'
    })
  ].filter(Boolean)
}

export default config
