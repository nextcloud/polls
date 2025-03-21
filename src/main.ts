/**
 * SPDX-FileCopyrightText: 2018 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createApp } from 'vue'
import { pinia } from './stores/index.ts'
import { router } from './router.ts'

import App from './App.vue'
import ClickOutside from 'v-click-outside'

// TODO: FInd a way to use the devtools in the browser
// Vue.config.devtools = import.meta.env.MODE !== 'production'

const Polls = createApp(App)
	.use(pinia)
	.use(router)
	.use(ClickOutside)
	.directive('focus', {
		mounted: (el) => el.focus(),
	})
Polls.mount('#content_polls')
