/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import {
	RouteLocationNormalized,
	RouteRecordRaw,
	createWebHistory,
	createRouter,
} from 'vue-router'

import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'
import { getCookieValue, Logger, setCookie } from './helpers'
import { loadContext } from './composables/context'

import Navigation from './views/Navigation.vue'

import { useSessionStore } from './stores/session'

async function validateToken(to: RouteLocationNormalized) {
	const sessionStore = useSessionStore()

	try {
		await sessionStore.loadShare()

		// if the user is logged in, reroute to the vote page
		if (getCurrentUser()) {
			return {
				name: 'vote',
				params: {
					id: sessionStore.share.pollId,
				},
			}
		}
	} catch (error) {
		if (getCurrentUser()) {
			// User has no access, always assume forbidden (403)
			return { name: 'forbidden' }
		}

		// external users will get redirected to the login page
		window.location.replace(generateUrl('login'))
	}

	// Continue for external users
	//
	if (sessionStore.share.type === 'public') {
		// Check, if user has a personal token from the user's client stored cookie
		// matching the public token
		const personalToken = getCookieValue(to.params.token as string)

		if (personalToken) {
			// participant has already access to the poll and a private token
			// extend expiry time for 30 days after successful access
			const cookieExpiration = 30 * 24 * 60 * 1000
			setCookie(to.params.token as string, personalToken, cookieExpiration)

			// reroute to the public vote page using the personal token
			return {
				name: 'publicVote',
				params: {
					token: personalToken,
				},
			}
		}
	}
}

const Combo = () => import('./views/Combo.vue')
const Forbidden = () => import('./views/Forbidden.vue')
const List = () => import('./views/PollList.vue')
const NotFound = () => import('./views/NotFound.vue')
const Vote = () => import('./views/Vote.vue')

const SideBar = () => import('./views/SideBar.vue')
const SideBarPollGroup = () => import('./views/SideBarPollGroup.vue')
const SideBarCombo = () => import('./views/SideBarCombo.vue')

const routes: RouteRecordRaw[] = [
	{
		name: 'list',
		path: '/list/:type?',
		components: {
			default: List,
			navigation: Navigation,
		},
		props: true,
		meta: {
			listPage: true,
		},
	},
	{
		name: 'group',
		path: '/group/:slug',
		components: {
			default: List,
			navigation: Navigation,
			sidebar: SideBarPollGroup,
		},
		props: true,
		meta: {
			groupPage: true,
			listPage: true,
		},
	},
	{
		name: 'combo',
		path: '/combo',
		components: {
			default: Combo,
			navigation: Navigation,
			sidebar: SideBarCombo,
		},
		meta: {
			comboPage: true,
		},
	},
	{
		name: 'notfound',
		path: '/not-found',
		components: {
			default: NotFound,
			navigation: Navigation,
		},
		meta: {
			errorPage: true,
		},
	},
	{
		name: 'forbidden',
		path: '/forbidden',
		components: {
			default: Forbidden,
			navigation: Navigation,
		},
		meta: {
			errorPage: true,
		},
	},
	{
		name: 'vote',
		path: '/vote/:id',
		components: {
			default: Vote,
			navigation: Navigation,
			sidebar: SideBar,
		},
		props: true,
		meta: {
			votePage: true,
		},
	},
	{
		name: 'publicVote',
		path: '/s/:token',
		components: {
			default: Vote,
			sidebar: SideBar,
		},
		beforeEnter: validateToken,
		props: true,
		meta: {
			publicPage: true,
			votePage: true,
		},
	},
	{
		name: 'root',
		path: '/',
		redirect: {
			name: 'list',
			params: {
				type: 'relevant',
			},
		},
	},
	{
		path: '/list',
		redirect: {
			name: 'list',
			params: {
				type: 'relevant',
			},
		},
	},
]

const router = createRouter({
	history: createWebHistory(generateUrl('/apps/polls')),
	routes,
	linkActiveClass: 'active',
})

router.beforeEach(
	async (to: RouteLocationNormalized, from: RouteLocationNormalized) => {
		const sessionStore = useSessionStore()

		// if the previous and the requested routes have the same name and
		// the watcher is active, we can do a cheap loading
		const cheapLoading =
			to.name === from.name
			&& sessionStore.watcher.mode !== 'noPolling'
			&& sessionStore.watcher.status !== 'stopped'

		// first load app context -> session and preferences
		try {
			await loadContext(to, cheapLoading)
		} catch (error) {
			Logger.error('Could not load context', { error })

			if (!sessionStore.userStatus.isLoggedin) {
				// if the user is not logged in, redirect to the login page
				window.location.replace(generateUrl('login'))
				return false
			}

			// if context can't be loaded, redirect to not found page
			return {
				name: 'notfound',
			}
		}
	},
)

export { router }
