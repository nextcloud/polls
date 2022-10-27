/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2022 Michael Longo <contact@tiller.fr>
 *
 * @author Michael Longo <contact@tiller.fr>
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

import Vue from 'vue'
import store from './store/index.js'
import { getRequestToken, getCurrentUser } from '@nextcloud/auth'
import { translate, translatePlural } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'

import Dashboard from './views/Dashboard.vue'
import './assets/scss/polls-icon.scss'

Vue.config.debug = process.env.NODE_ENV !== 'production'
Vue.config.devTools = process.env.NODE_ENV !== 'production'

/* eslint-disable-next-line camelcase, no-undef */
__webpack_nonce__ = btoa(getRequestToken())
/* eslint-disable-next-line camelcase, no-undef */
__webpack_public_path__ = generateFilePath('polls', '', 'js/')

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.getCurrentUser = getCurrentUser

document.addEventListener('DOMContentLoaded', () => {
	OCA.Dashboard.register('polls', (el) => {
		const View = Vue.extend(Dashboard)
		new View({
			propsData: {},
			store,
		}).$mount(el)
	})
})
