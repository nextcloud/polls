/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2019 René Gieling <github@dartcafe.de>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import Vue from 'vue'
import Router from 'vue-router'
import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'
import { getCookieValue, setCookie } from './helpers/index.js'
import { PublicAPI } from './Api/index.js'
import Vote from './views/Vote.vue'
import SideBar from './views/SideBar.vue'

import List from './views/PollList.vue'
import Administration from './views/Administration.vue'
import NotFound from './views/NotFound.vue'
import SideBarCombo from './views/SideBarCombo.vue'
import Navigation from './views/Navigation.vue'
import Combo from './views/Combo.vue'

Vue.use(Router)

/**
 * @callback nextCallback
 * @param {object} route
 */

/**
 * @param {object} to Target route
 * @param {object} from  Route navigated from
 * @param {nextCallback} next callback for next route
 */
async function validateToken(to, from, next) {
	try {
		const response = await PublicAPI.getShare(to.params.token)
		if (getCurrentUser()) {
			// reroute to the internal vote page, if the user is logged in
			next({ name: 'vote', params: { id: response.data.share.pollId } })

		} else {

			const privateToken = getCookieValue(to.params.token)

			if (privateToken && privateToken !== to.params.token) {
				// extend expiry time for 30 days after successful access
				const cookieExpiration = (30 * 24 * 60 * 1000)
				setCookie(to.params.token, privateToken, cookieExpiration)
				next({ name: 'publicVote', params: { token: privateToken } })
			} else {
				next()
			}
		}
	} catch (error) {
		if (getCurrentUser()) {
			next({ name: 'notfound' })
		} else {
			window.location.replace(generateUrl('login'))
		}

	}
}

export default new Router({
	mode: 'history',
	base: generateUrl('/apps/polls'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			redirect: {
				name: 'list',
				params: {
					type: 'relevant',
				},
			},
			name: 'root',
			meta: {
				publicPage: false,
			}

		},
		{
			path: '/list',
			redirect: {
				name: 'list',
				params: {
					type: 'relevant',
				},
			},
			meta: {
				publicPage: false,
			}
		},
		{
			path: '/list/:type?',
			components: {
				default: List,
				navigation: Navigation,
			},
			props: true,
			name: 'list',
			meta: {
				publicPage: false,
			}
		},
		{
			path: '/administration',
			components: {
				default: Administration,
				navigation: Navigation,
			},
			name: 'administration',
			meta: {
				publicPage: false,
			}
		},
		{
			path: '/combo',
			components: {
				default: Combo,
				navigation: Navigation,
				sidebar: SideBarCombo,
			},
			name: 'combo',
			meta: {
				publicPage: false,
			}
		},
		{
			path: '/not-found',
			components: {
				default: NotFound,
				navigation: Navigation,
			},
			name: 'notfound',
			meta: {
				publicPage: false,
			}
		},
		{
			path: '/vote/:id',
			components: {
				default: Vote,
				navigation: Navigation,
				sidebar: SideBar,
			},
			props: true,
			name: 'vote',
			meta: {
				publicPage: false,
			}
		},
		{
			path: '/s/:token',
			components: {
				default: Vote,
				sidebar: SideBar,
			},
			beforeEnter: validateToken,
			props: true,
			name: 'publicVote',
			meta: {
				publicPage: true,
			}
		},
	],
})
