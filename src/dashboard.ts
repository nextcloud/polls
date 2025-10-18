/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { pinia } from './stores'

import Dashboard from './views/Dashboard.vue'
import './assets/scss/polls-icon.scss'

/** global: OCA */
document.addEventListener('DOMContentLoaded', () => {
	// @ts-expect-error: Name not found error
	OCA.Dashboard.register('polls', (el) => {
		const PollsDashboard = createApp(Dashboard).use(pinia).mount(el)

		return PollsDashboard
	})
})
