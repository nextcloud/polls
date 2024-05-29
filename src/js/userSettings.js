/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import settings from './store/modules/settings.js'
import { translate, translatePlural } from '@nextcloud/l10n'

import UserSettingsPage from './views/UserSettingsPage.vue'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

Vue.config.devtools = import.meta.env.MODE !== 'production'

Vue.use(Vuex)

const store = new Store({
	modules: {
		settings,
	},
	strict: process.env.NODE_ENV !== 'production',
})

/* eslint-disable-next-line no-new */
new Vue({
	el: '#content_polls',
	store,
	render: (h) => h(UserSettingsPage),
})
