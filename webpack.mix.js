let mix = require('laravel-mix')
let path = require('path')
require('./nova.mix')

mix.alias({
  '@': path.join(__dirname, '../../vendor/laravel/nova/resources/js'),
})

// mix.alias({
//   'laravel-nova': path.join(__dirname, '../../vendor/laravel/nova/resources/js/mixins/'),
// });

mix
  .setPublicPath('dist')
  .js('resources/js/field.js', 'js')
  .vue({ version: 3 })
  .css('resources/css/field.css', 'css')
  .nova('sprigs/filterable-belongs-to-many')
