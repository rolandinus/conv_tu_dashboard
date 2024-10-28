import Vue from 'vue'
import App from './App.vue'

console.log('INIT main.js')
Vue.mixin({ methods: { t, n } })

const View = Vue.extend(App)
new View().$mount('#tu-dashboard')
