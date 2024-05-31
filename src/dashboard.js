/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import store from './store/store-polls.js'

import Dashboard from './views/Dashboard.vue'
import './assets/scss/polls-icon.scss'

Vue.config.devtools = import.meta.env.MODE !== 'production'

document.addEventListener('DOMContentLoaded', () => {
	OCA.Dashboard.register('polls', (el) => {
		const View = Vue.extend(Dashboard)
		new View({
			propsData: {},
			store,
		}).$mount(el)
	})
})
