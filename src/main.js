import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import axiosInstance from './axios-instance'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'

const app = createApp(App)
app.config.globalProperties.$axios = axiosInstance
app.config.globalProperties.$sendMsg = function (isError, text) {
  window.dispatchEvent(new CustomEvent('globalMessage', { detail: { isError, text } }))
}
app.use(router)
app.mount('#app')
