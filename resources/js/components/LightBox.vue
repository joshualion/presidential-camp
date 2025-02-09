<template>
    <div>
      <!-- Lightbox Overlay -->
      <div
        v-if="isVisible"
        id="lightbox-overlay"
        @click="closeLightbox"
      >
        <!-- Enlarged Image -->
        <img :src="currentImageSrc" id="lightbox-image" />
  
        <!-- Close Button -->
        <span id="lightbox-close" @click="closeLightbox">&times;</span>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    props: {
      // Current image source to display in the lightbox
      currentImageSrc: {
        type: String,
        default: '',
      },
      // Controls the visibility of the lightbox
      isVisible: {
        type: Boolean,
        default: false,
      },
    },
    methods: {
      // Close the lightbox
      closeLightbox() {
        this.$emit('close-lightbox');
      },
    },
  };
  </script>
  
  <style scoped>
  /* Lightbox overlay */
  #lightbox-overlay {
    display: flex;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }
  
  /* Enlarged image */
  #lightbox-image {
    max-width: 90%;
    max-height: 90%;
    border: 2px solid #fff;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
  }
  
  /* Close button */
  #lightbox-close {
    position: absolute;
    top: 20px;
    right: 20px;
    color: #fff;
    font-size: 30px;
    cursor: pointer;
  }
  </style>