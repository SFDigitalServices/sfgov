cd web/themes/custom/sfgovpl
npm ci
export NODE_ENV=production
npm run build
npm i --force # remove dev dependencies