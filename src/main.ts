/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import { pinia } from './stores/index.ts'
import App from './App.vue'
import router from './router.js'
import ClickOutside from 'v-click-outside'
import { Tooltip } from '@nextcloud/vue'

Vue.config.devtools = import.meta.env.MODE !== 'production'

// eslint-disable-next-line vue/match-component-file-name
Vue.directive('tooltip', Tooltip)

Vue.use(ClickOutside)
Vue.use(PiniaVuePlugin)

/* eslint-disable-next-line no-new */
const app = new Vue({
	router,
	pinia,
	render: (h) => h(App),
})
app.$mount('#content')
