<template>
  <div class="layout-wrapper">
    <div class="layout-header">
      <div class="image">
        <h1>
          <slot name="header"></slot>
        </h1>
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
  background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('@/assets/suits.webp');
  background-size: cover;
}

.content {
  padding: 30px 15px;
  background-color: white;
  padding-bottom: 80px;
  flex-grow: 3;
  max-width: 100vw;
}


.container {
  max-width: 1020px;
  margin: 0 auto;
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

  .footer-wrapper {
    display: none;
  }

  nav {
    display: none;
  }
}
</style>
