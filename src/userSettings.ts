/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import { pinia } from './stores/index.ts'

import UserSettingsPage from './views/UserSettingsPage.vue'

Vue.config.devtools = import.meta.env.MODE !== 'production'

Vue.use(PiniaVuePlugin)

/* eslint-disable-next-line no-new */
new Vue({
	pinia,
	render: (h) => h(UserSettingsPage),
}).$mount('#content_polls')
