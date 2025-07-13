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
import { getCookieValue, Logger, setCookie } from './helpers/index.ts'
import { loadContext } from './composables/context.ts'
import { AxiosError } from 'axios'

import Navigation from './views/Navigation.vue'

import Combo from './views/Combo.vue'
import Forbidden from './views/Forbidden.vue'
import List from './views/PollList.vue'
import NotFound from './views/NotFound.vue'
import Vote from './views/Vote.vue'

import SideBar from './views/SideBar.vue'
import SideBarPollGroup from './views/SideBarPollGroup.vue'
import SideBarCombo from './views/SideBarCombo.vue'

import { usePollStore } from './stores/poll.ts'
import { useSessionStore } from './stores/session.ts'

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
			if ((error as AxiosError).response?.status === 403) {
				// User has no access
				return { name: 'forbidden' }
			}
			// in case of other errors, reroute internal user to the not found page
			return { name: 'notfound' }
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

	// finally load the poll
	const pollStore = usePollStore()

	pollStore.load()
}

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
