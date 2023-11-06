<template>
  <div class="wrapper">
    <div class="header">
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
    <footer class="flex flex-column">
      <div class="footer-wrapper">
        <slot name="footer"></slot>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.header {
  background-color: #272727;
  color: white;

}

.header .image {
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

.body {
  min-height: 100vh;
}

footer {
  background-color: #272727;
  color: lightgray;
}

.footer-wrapper {
  padding: 10px;

  background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('@/assets/suits.webp');
  background-size: cover;
  display: flex;
  justify-content: center;
}

</style>
