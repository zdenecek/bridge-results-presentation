<template>
    <router-view />
</template>



<script setup lang="ts">


</script>

<style>
* {
  box-sizing: border-box;
}
body {
  padding: 0;
  margin: 0;
  max-width: 100vw;
}



.container {
  max-width: 1020px;
  margin: 0 auto;
}


#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}


nav {
  padding: 10px 30px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

nav a, .nav-button {
  display: flex;
  align-items: center;
  justify-content: center;

  border: 1px solid #2c3e50;
  border-radius: 5px;
  padding: 2px 8px;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    /* display: none; <- Crashes Chrome on hover */
    -webkit-appearance: none;
    margin: 0;
    /* <-- Apparently some margin are still there even though it's hidden */
}

input[type=number] {
    -moz-appearance: textfield;
    /* Firefox */
}

.button {
  border-radius: 5px;
  border: 1px solid #2c3e50;
  padding: 2px 8px;
}

nav a.router-link-exact-active {
  color: white;
  font-weight: bold;
  border: 1px solid #1a881a;
  background-color: #1a881a;
}

nav a:hover, .nav-button:hover {
  border: 1px solid #1a881a;
  background-color: #1a881a ;
  color: white;
}

h2, h3 {
  color: #1a881a;
}

a, a:visited {
  color: #1a881a;
  text-decoration: none;
}

a:hover {
  color: darkgreen;
}

.flex {
  display: flex;
}

.flex-column {
  flex-direction: column;
}

.flex-center {
  justify-content: center;
  max-width: 100vw;
}

.flex-center > * {
  margin: auto
}

td.col-name {
  min-width: 200px;
}

.table td,
.table th {
  padding: 4px 6px;
  border: 1px solid #2c3e50;

}

.table {
  border-collapse: collapse;
  overflow-x: auto;
  background-color: white;
}

.table tr:hover {
  background-color: #f5f5f5;
}

</style>
