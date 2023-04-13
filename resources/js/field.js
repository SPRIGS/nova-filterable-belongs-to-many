import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('index-filterable-belongs-to-many', IndexField)
  app.component('detail-filterable-belongs-to-many', DetailField)
  app.component('form-filterable-belongs-to-many', FormField)
})
