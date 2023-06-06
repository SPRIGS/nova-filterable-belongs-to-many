<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <div v-if="field.value.length > 0">
        <TagGroup v-if="field.style === 'group'" :tags="field.value" :resource-name="field.resourceName" :editable="false"
          :with-preview="field.withPreview" />
        <div v-if="field.style === 'list'">
          <ul class="list-disc break-normal">
            <li class="mb-2" v-for="item in field.value" :key="item.display">{{ item.display }}</li>
          </ul>
        </div>
      </div>
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],
}
</script>

<style scoped>
.list-disc {
  list-style-type: disc;
}
</style>