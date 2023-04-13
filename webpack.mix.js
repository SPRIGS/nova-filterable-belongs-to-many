let mix = require('laravel-mix')
let path = require('path')
require('./nova.mix')

mix.alias({
  '@': path.join(__dirname, '../../laravel/nova/resources/js'),
})

mix
  .setPublicPath('dist')
  .js('resources/js/field.js', 'js')
  .vue({ version: 3 })
  .css('resources/css/field.css', 'css')
  .nova('sprigs/filterable-belongs-to-many')
