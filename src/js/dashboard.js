/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2022 Michael Longo <contact@tiller.fr>
 *
 * @author Michael Longo <contact@tiller.fr>
 * @author René Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { createApp } from 'vue'
import pineaPolls from './store/pinea-polls.js'
import { getCurrentUser } from '@nextcloud/auth'
import { translate, translatePlural } from '@nextcloud/l10n'

import Dashboard from './views/Dashboard.vue'
import './assets/scss/polls-icon.scss'

// Vue.config.debug = import.meta.env.MODE === 'development'
// Vue.config.devtools = import.meta.env.MODE === 'development'

document.addEventListener('DOMContentLoaded', () => {
	OCA.Dashboard.register('polls', (el) => {
		const PollsDashboard = createApp(Dashboard)
			.use(pineaPolls)
			.mount(el)
		PollsDashboard.config.globalProperties.t = translate
		PollsDashboard.config.globalProperties.n = translatePlural
		PollsDashboard.config.globalProperties.getCurrentUser = getCurrentUser

		return PollsDashboard
	})
})
