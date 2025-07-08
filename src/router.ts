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
import { FilterType } from './stores/polls.ts'
import { useSessionStore } from './stores/session.ts'
import { ShareType } from './stores/shares.ts'

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
	if (sessionStore.share.type === ShareType.Public) {
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
		path: '/list/:type?',
		components: {
			default: List,
			navigation: Navigation,
		},
		props: true,
		name: 'list',
		meta: {
			listPage: true,
		},
	},
	{
		path: '/group/:slug',
		components: {
			default: List,
			navigation: Navigation,
			sidebar: SideBarPollGroup,
		},
		props: true,
		name: 'group',
		meta: {
			groupPage: true,
			listPage: true,
		},
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
			comboPage: true,
		},
	},
	{
		path: '/not-found',
		components: {
			default: NotFound,
			navigation: Navigation,
		},
		name: 'notfound',
	},
	{
		path: '/forbidden',
		components: {
			default: Forbidden,
			navigation: Navigation,
		},
		name: 'forbidden',
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
			votePage: true,
		},
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
		},
	},
	{
		path: '/',
		name: 'root',
		redirect: {
			name: 'list',
			params: {
				type: FilterType.Relevant,
			},
		},
	},
	{
		path: '/list',
		redirect: {
			name: 'list',
			params: {
				type: FilterType.Relevant,
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

	// 	try {
	// 		// for public pages we need to load the share first
	// 		if (to.meta.publicPage) {
	// 			await sessionStore.loadShare()
	// 		}

	// 		// vote pages load the particular poll
	// 		// or reset the poll store if not a vote page
	// 		if (to.meta.votePage) {
	// 			// pollStore.load()
	// 		} else {
	// 			// pollStore.resetPoll()
	// 		}

	// 		// load polls at least for navigation
	// 		if (!to.meta.publicPage && !cheapLoading) {
	// 			await pollsStore.load()
	// 		}

	// 		// group pages need shares for the current poll group
	// 		if (to.meta.groupPage) {
	// 			sharesStore.load('pollGroup')
	// 		}
	// 	} catch (error) {
	// 		Logger.warn('Could not load poll', { error })
	// 		if ((error as AxiosError).response?.status === 403) {
	// 			// User has no access
	// 			return {
	// 				name: 'forbidden',
	// 			}
	// 		}
	// 		// else let's pretend, the poll does not exist (what will be probably the case)
	// 		return {
	// 			name: 'notfound',
	// 		}
	// 	}
	},
)

export { router }
