const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,
  configureWebpack: {
    devServer: {
      // TODO: remove, insecure
      headers: { "Access-Control-Allow-Origin": "*" }
    }
  }
})
