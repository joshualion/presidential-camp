<template>
    <div class="pagination-container">
      <ul class="pagination">
        <li v-for="link in links" :key="link.label" :class="{ disabled: link.active }">
          <a href="#" @click.prevent="handlePageChange(link.url)">{{ link.label }}</a>
        </li>
      </ul>
    </div>
  </template>
  
  <script>
  export default {
    props: ['links'],
    methods: {
      handlePageChange(url) {
        const page = new URL(url).searchParams.get('page');
        this.$emit('page-changed', page);
      }
    }
  };
  </script>
  