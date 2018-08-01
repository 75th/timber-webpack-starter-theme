const path = require('path');

module.exports = {
  THEMENAME: 'THEME-NAME',
  PROXY_TARGET: 'rival.sft',
  HOST: 'localhost',
  PORT: 3000,
  PATHS: {
    src: unipath('src'),
    compiled: unipath(path.resolve(__dirname, 'compiled')),
    modules: unipath('node_modules'),
    base: unipath('.'),
  }
};

function unipath(base) {
  return function join() {
    const _paths = [base].concat(Array.from(arguments));
    return path.resolve(path.join.apply(null, _paths));
  }
}
