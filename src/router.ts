/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { RouteLocationNormalized, RouteRecordRaw, createWebHistory, createRouter } from 'vue-router'

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
import { FilterType } from './stores/polls.ts'
import { useSessionStore } from './stores/session.ts'

async function validateToken(to: RouteLocationNormalized) {

	if (getCurrentUser()) {
		try {
			const response = await PublicAPI.getShare(to.params.token)
			// if the user is logged in, we diretly route to 
			// the internal vote page
			return {
				name: 'vote',
				params: {
					id: response.data.share.pollId 
				}
			}
		} catch (error) {
			// in case of an error, reroute to the not found page
			return {
				name: 'notfound'
			}
		}
	}
	
	// continue for external users
	try {
		// first validate the existance of the public token
		await PublicAPI.getShare(to.params.token)
	} catch (error) {
		// in case of an error, reroute to the login page
		window.location.replace(generateUrl('login'))
	}

	// then look for an existing personal token from 
	// the user's client stored cookie
	// matching the public token
	const personalToken = getCookieValue(<string>to.params.token)

	if (personalToken && personalToken !== to.params.token) {
		// participant has already access to the poll and a private token
		// extend expiry time for 30 days after successful access
		const cookieExpiration = (30 * 24 * 60 * 1000)
		setCookie(<string>to.params.token, personalToken, cookieExpiration)

		// reroute to the public vote page using the personal token
		return {
			name: 'publicVote',
			params: {
				token: personalToken
			}
		}
	}

	// if no private token is found, load the poll
	const pollStore = usePollStore()
	await pollStore.load()
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
			votePage: false,
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
			votePage: false,
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
			votePage: false,
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
			votePage: false,
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
			votePage: true,
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
			votePage: true,
		}
	},
	{
		path: '/',
		redirect: {
			name: 'list',
			params: {
				type: FilterType.Relevant,
			},
		},
		name: 'root',
		meta: {
			publicPage: false,
			votePage: false,
		}

	},
	{
		path: '/list',
		redirect: {
			name: 'list',
			params: {
				type: FilterType.Relevant,
			},
		},
		meta: {
			publicPage: false,
		}
	},
]

const router = createRouter({
	history: createWebHistory(generateUrl('/apps/polls')),
	routes,
	linkActiveClass: 'active',
})

router.beforeEach(async (to: RouteLocationNormalized) => {
	const sessionStore = useSessionStore()
	const pollStore = usePollStore()
	sessionStore.setRouter(to)
	try {
		await loadContext(to)
	} catch (error) {
		Logger.error('Could not load context')
		return false
	}

	try {
		if (to.meta.publicPage) {
			await sessionStore.loadShare()
		}

		if (to.meta.votePage) {
			await pollStore.load()
		}
	
	} catch (error) {
		Logger.warn('Could not load poll', error)
		return {
			name: 'notfound',
		}
	}
})

export { router }