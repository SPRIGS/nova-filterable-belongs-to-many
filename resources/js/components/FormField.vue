<template>
  <DefaultField :field="currentField" :errors="errors" :show-help-text="showHelpText"
    :full-width-content="fullWidthContent">
    <template #field>
      <DefaultButton component="button" size="sm" type="button" dusk="confirm-action-button" class="ml-auto"
        @click="showResourcesTableModal = true">
        Link {{ currentField.name }} ({{ field.value.length }})
      </DefaultButton>


      <Modal :show="showResourcesTableModal" @close-via-escape="showResourcesTableModal = false"
        data-testid="confirm-action-modal" tabindex="-1" role="dialog" :size="'5xl'" :modal-style="'window'">
        <form ref="theForm" autocomplete="off" @change="onUpdateFormStatus" @submit.prevent.stop="$emit('confirm')"
          :data-form-unique-id="formUniqueId"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden space-y-6">
          <div>
            <ModalHeader v-text="`Linked ${currentField.name}`" />

            <div class="p-4">

              <!-- Table filters -->

              <div class="items-end grid grid-cols-4 gap-4 mb-4">
                <SearchSearchInput debounce="100" @input="search = $event" class="w-full">
                </SearchSearchInput>


                <div class="relative w-full " v-for="filter in availableFilters">
                  <span>{{ filter.label }}</span>
                  <SelectControl 
                  v-model:selected="filter.value"
                  :options="filter.options"
                   @change="filterChanged(filter, $event)" size="sm" />
                </div>
              </div>

              <!-- End of Table filters -->

              <div class="overflow-hidden overflow-x-auto relative">
                <table v-if="resources.length > 0" class="w-full divide-y divide-gray-100 dark:divide-gray-700"
                  data-testid="resource-table">
                  <ResourceTableHeader :resource-name="resourceName" :fields="resources[0].fields"
                    :should-show-column-borders="shouldShowColumnBorders" :should-show-checkboxes="true"
                    :sortable="sortable" @order="requestOrderByChange" @reset-order-by="resetOrderBy" />
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <ResourceTableRow v-for="(resource, index) in filteredResources"
                      @actionExecuted="$emit('actionExecuted')" :testId="`${resourceName}-items-${index}`"
                      :key="`${resource.id.value}-items-${index}`" :resource="resource" :resource-name="resourceName"
                      :checked="field.value.map(v => v.value).indexOf(resource.id.value) > -1"
                      :actions-are-available="false" :actions-endpoint="actionsEndpoint" :should-show-checkboxes="true"
                      :should-show-column-borders="shouldShowColumnBorders" :table-style="tableStyle"
                      :update-selection-status="updateSelectionStatus" :click-action="'ignore'" />
                  </tbody>
                </table>
                <div v-if="filteredResources.length == 0" class="w-full text-center p-4">
                  No results found.
                </div>
              </div>
            </div>
          </div>

          <ModalFooter>
            <div class="flex items-center ml-auto">
              <DefaultButton component="button" type="button" dusk="confirm-action-button" class="ml-auto"
                @click="showResourcesTableModal = false">Close</DefaultButton>
            </div>
          </ModalFooter>
        </form>
      </Modal>

      <div class="space-y-4">

        <div v-if="value.length > 0">
          <TagList v-if="field.style === 'list'" :tags="value" @tag-removed="i => removeResource(i)"
            :resource-name="field.resourceName" :editable="!currentlyIsReadonly" :with-preview="field.withPreview" />

          <TagGroup v-if="field.style === 'group'" :tags="value" @tag-removed="i => removeResource(i)"
            :resource-name="field.resourceName" :editable="!currentlyIsReadonly" :with-preview="field.withPreview" />
        </div>
      </div>


    </template>
  </DefaultField>
</template>

<script>
import {
  DependentFormField,
  PerformsSearches,
  HandlesValidationErrors,
  mapProps
} from '@/mixins'

import { minimum } from '@/util'
import { search as hasManySearch } from '@/storage/HasManyFieldStorage'

export default {
  name: 'FilterableBelongsToManyFormField',
  mixins: [DependentFormField, PerformsSearches, HandlesValidationErrors],

  props: {
    ...mapProps(['resourceId']),
  },

  data: () => ({
    showResourcesTableModal: false,
    resources: [],
    availableFilters: [],
    value: [],
  }),


  mounted() {
    Nova.request().get(`/nova-api/${this.field.resourceName}`)
      .then((data) => {
        this.resources = data.data.resources;

        this.resources.forEach(resource => {
          resource.fields = resource.fields.filter(field => !this.field.hiddenFields.includes(field.attribute))
        })

        this.availableFilters = this.getAvailableFilters()
      });
  },

  computed: {
    filteredResources() {
      

      const filterMatches = (resource, filter) => {
        if (filter.value === 'all') return true;

        const matchingField = resource.fields.find(field => field.attribute === filter.name);
        return matchingField && matchingField.value === filter.value;
      };

      const searchMatches = (resource) =>
        resource.title.toLowerCase().includes(this.search.toLowerCase());

      return this.resources.filter(resource =>
        this.availableFilters.every(filter => filterMatches(resource, filter)) && searchMatches(resource)
      );

      
    }

  },

  methods: {
    fill(formData) {
      this.fillIfVisible(
        formData,
        this.currentField.attribute,
        this.value.length > 0 ? JSON.stringify(this.value) : ''
      )
    },
    getAvailableFilters() {
      const createBaseFilter = (filter) => ({
        label: filter,
        name: filter.replace(/([A-Z])/g, ' $1').trim().replace(/ /g, '_').toLowerCase(),
        value: 'all',
        options: [{ value: 'all', label: 'All' }],
      });

      const addOptionToFilter = (filters, field) => {
        const filter = filters.find(filter => filter.name === field.attribute);

        if (filter) {
          const optionExists = filter.options.some(option => option.value === field.value);

          if (!optionExists) {
            filter.options.push({
              value: field.value,
              label: field.value.charAt(0).toUpperCase() + field.value.slice(1),
            });
          }
        }

        return filters;
      };

      const baseFilters = this.field.availableFilters.map(createBaseFilter);

      return this.resources.reduce((filters, resource) => {
        resource.fields.forEach(field => {
          filters = addOptionToFilter(filters, field);
        });

        return filters;
      }, baseFilters);
    },

    filterChanged(filter, event) {
      filter.value = event
    },


    removeResource(index) {
      this.value.splice(index, 1)
    },

    updateSelectionStatus(resource) {
      let selectedResource = {
        value: resource.id.value,
        display: resource.title
      };
      console.log(selectedResource);
      // uf the resource is already selected, remove it
      if (this.value.map(v => v.value).indexOf(resource.id.value) > -1) {
        this.value = this.value.filter(v => v.value !== resource.id.value);
      } else {
        // otherwise add it
        this.value.push(selectedResource);
      }
    },
  },
}
</script>
<style scoped>
.pb-6 {
  padding-bottom: 1.5rem;
}

.mb-0 {
  margin-bottom: 0;
}

.gap-4 {
  gap: 1rem;
}

.items-end {
  align-items: flex-end;
}

@media (min-width: 768px) {
  .md\:w-full {
    width: 100%;
  }
}
</style>