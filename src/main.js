import { linkTo } from '@nextcloud/router'
import Vue from 'vue'
import App from './App.vue'

__webpack_nonce__ = window.btoa(OC.requestToken) // eslint-disable-line no-undef, camelcase, no-global-assign
__webpack_public_path__ = linkTo('tuuls_dashboard', 'js/') // eslint-disable-line no-undef, camelcase, no-global-assign

Vue.mixin({ methods: { t, n } })

new Vue({
	render: h => h(App),
}).$mount('#tu-dashboard')
