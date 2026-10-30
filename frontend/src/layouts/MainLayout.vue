<template>
  <div class="layout-wrapper">
    <club-bar />
    <site-banner />
    <div class="layout-header">
      <div class="image">
        <slot name="hero">
          <h1>
            <slot name="header"></slot>
          </h1>
        </slot>
      </div>
    </div>
    <div class="content">
      <div class="container">
        <slot></slot>
      </div>
    </div>
    <footer>
      <div class="footer-wrapper">
        <slot name="footer"></slot>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import ClubBar from '@/components/ClubBar.vue';
import SiteBanner from '@/components/SiteBanner.vue';
</script>

<style>
.layout-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.layout-header {
  background-color: #272727;
  color: white;
}

.layout-header .image {
  padding: 15px;
  /* Dark enough that the title reads over the red suits. */
  background: linear-gradient(rgba(0, 0, 0, 0.66), rgba(0, 0, 0, 0.66)), url('@/assets/suits.webp');
  background-size: cover;
}

.layout-header h1 {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8), 0 0 18px rgba(0, 0, 0, 0.6);
}

.content {
  padding: 30px 15px;
  background-color: white;
  padding-bottom: 80px;
  flex-grow: 3;
  max-width: 100%;
}


.container {
  max-width: 1020px;
  margin: 0 auto;
}

@media (max-width: 640px) {
  .content {
    padding: 20px 4px 60px;
  }
}


nav {
  padding: 10px 30px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

.body {
  min-height: 100vh;
}

footer {
  background-color: #272727;
  color: lightgray;
  display: flex;
  flex-direction: column;
}

.footer-wrapper {
  padding: 10px;
  background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('@/assets/suits.webp');
  background-size: cover;
  display: flex;
  justify-content: center;
}

@media print {



  h3, h2 {
    margin: 0;
  }

  .layout-header {
    background-color: unset;
    color: var(--primary-color);
  }

  @page {
     margin: 25mm 25mm 25mm 25mm;  
  }

  .content {
    padding: 0;
  }

  .container {
    max-width: unset;
  }

  .layout-header .image {
    padding: 0;
    background: unset;
  }

  .layout-header h1 {
    text-shadow: none;
  }

  .footer-wrapper {
    display: none;
  }

  nav {
    display: none;
  }
}
</style>
