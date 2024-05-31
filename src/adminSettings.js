/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import appSettings from './store/modules/appSettings.js'
import AdminSettingsPage from './views/AdminSettingsPage.vue'

Vue.config.devtools = import.meta.env.MODE !== 'production'

Vue.use(Vuex)

const store = new Store({
	modules: {
		appSettings,
	},
	strict: process.env.NODE_ENV !== 'production',
})

/* eslint-disable-next-line no-new */
new Vue({
	el: '#content_polls',
	store,
	render: (h) => h(AdminSettingsPage),
})
