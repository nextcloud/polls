/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { RouteLocationNormalized, RouteRecordRaw, createMemoryHistory, createRouter } from 'vue-router'

import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'
import { getCookieValue, Logger, setCookie } from './helpers/index.ts'
import { PublicAPI } from './Api/index.js'
import { loadContext } from './composables/context.ts'
import Vote from './views/Vote.vue'
import SideBar from './views/SideBar.vue'

import List from './views/PollList.vue'
import Administration from './views/Administration.vue'
import NotFound from './views/NotFound.vue'
import SideBarCombo from './views/SideBarCombo.vue'
import Navigation from './views/Navigation.vue'
import Combo from './views/Combo.vue'
import { usePollStore } from './stores/poll.ts'


/**
 * @param {RouteLocationNormalized} to Target route
 */
async function validateToken(to: RouteLocationNormalized) {

	if (getCurrentUser()) {
		try {
			const response = await PublicAPI.getShare(to.params.token)
			// if the user is logged in, we diretly route to the internal vote page
			return {
				name: 'vote',
				params: {
					id: response.data.share.pollId 
				}
			}
		} catch (error) {
			return {
				name: 'notfound'
			}
		}
	}
	
	try {
		// first get an existing private token from the cookie 
		// mathing the public token
		const privateToken = getCookieValue(to.params.token)
		if (privateToken && privateToken !== to.params.token) {
			// participant has already access to the poll and a private token
			// extend expiry time for 30 days after successful access
			const cookieExpiration = (30 * 24 * 60 * 1000)
			setCookie(to.params.token, privateToken, cookieExpiration)

			// reroute to the vote page with the private token
			return {
				name: 'publicVote',
				params: {
					token: privateToken
				}
			}
		}

	} catch (error) {
		// in all not found cases reroute to the lokgin page
		window.location.replace(generateUrl('login'))
	}
}
/**
 *
 */
async function loadPoll() {
	console.log('loadPoll before enter');
	
	const pollStore = usePollStore()
	await pollStore.load()
	// return {
	// 	name: 'vote',
	// 	params: {
	// 		id: to.params.id
	// 	}
	// }
}
const routes: RouteRecordRaw[] = [
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
		beforeEnter: loadPoll,
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
]

const router = createRouter({
	history: createMemoryHistory(generateUrl('/apps/polls')),
	routes,
	linkActiveClass: 'active',
})

router.beforeResolve((to: RouteLocationNormalized) => {
	console.log('beforeResolve', to)
})

router.beforeEach((to: RouteLocationNormalized) => {
	console.log('beforeEach', to)
	try {
		loadContext(to)
		console.log('context loaded')
		// if (to.name === 'vote') {
		// 	loadPoll(to)
		// }
		// return to
	} catch (error) {
		Logger.error('Could not load context')
		return false
	}
})

export { router }