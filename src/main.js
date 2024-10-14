
import Vue from 'vue';
import App from './App.vue';

import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'
Vue.component('v-select', vSelect)

Vue.prototype.t = t;
Vue.prototype.oc_defaults = oc_defaults;
Vue.prototype.OC = OC;


const root_element = document.getElementById("tu-dashboard");

const AppRoot = Vue.extend(App);
new AppRoot({
    el: root_element,
    propsData: { ...root_element.dataset }
});
