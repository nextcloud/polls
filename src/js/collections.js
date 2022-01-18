/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import './assets/scss/polls-icon.scss'

(function(OCP, OC) {

	// eslint-disable-next-line
	__webpack_nonce__ = btoa(OC.requestToken)
	// eslint-disable-next-line
	__webpack_public_path__ = OC.linkTo('polls', 'js/')

	Vue.config.debug = process.env.NODE_ENV !== 'production'
	Vue.config.devTools = process.env.NODE_ENV !== 'production'

	Vue.prototype.t = translate
	Vue.prototype.n = translatePlural
	Vue.prototype.OC = OC

	OCP.Collaboration.registerType('poll', {
		action: () => new Promise((resolve, reject) => {
			const container = document.createElement('div')
			container.id = 'polls-poll-select'
			const body = document.getElementById('body-user')
			body.appendChild(container)
			const PollSelector = () => import('./views/PollSelector')
			const ComponentVM = new Vue({
				render: (h) => h(PollSelector, {
					props: {
						// Even if it is used from Talk the Collections menu is
						// independently loaded, so the properties that depend
						// on the store need to be explicitly injected.
						container: window.store ? window.store.getters.getMainContainerSelector() : undefined,
					},
				}),
			})
			ComponentVM.$mount(container)
			ComponentVM.$root.$on('close', () => {
				ComponentVM.$el.remove()
				ComponentVM.$destroy()
				reject(new Error('User cancelled poll selection'))
			})
			ComponentVM.$root.$on('select', (id) => {
				resolve(id)
				ComponentVM.$el.remove()
				ComponentVM.$destroy()
			})
		}),
		typeString: t('poll', 'Link to a poll'),
		typeIconClass: 'icon-polls',
	})
})(window.OCP, window.OC)
