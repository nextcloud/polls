/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import App from './App.vue'
import { sync } from 'vuex-router-sync'
import store from './store/index.js'
import router from './router.js'
import ClickOutside from 'v-click-outside'
import { Tooltip } from '@nextcloud/vue'

sync(store, router)

Vue.config.devtools = import.meta.env.MODE !== 'production'

// eslint-disable-next-line vue/match-component-file-name
Vue.directive('tooltip', Tooltip)

Vue.use(ClickOutside)

/* eslint-disable-next-line no-new */
new Vue({
	el: '#content',
	router,
	store,
	render: (h) => h(App),
})
