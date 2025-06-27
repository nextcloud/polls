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
import { PublicAPI } from './Api/index.ts'
import { loadContext } from './composables/context.ts'
import Vote from './views/Vote.vue'
import SideBar from './views/SideBar.vue'

import List from './views/PollList.vue'
import NotFound from './views/NotFound.vue'
import SideBarCombo from './views/SideBarCombo.vue'
import Navigation from './views/Navigation.vue'
import Combo from './views/Combo.vue'
import { usePollStore } from './stores/poll.ts'
import { FilterType, usePollsStore } from './stores/polls.ts'
import { useSessionStore } from './stores/session.ts'
import SideBarPollGroup from './views/SideBarPollGroup.vue'
import { useSharesStore } from './stores/shares.ts'

async function validateToken(to: RouteLocationNormalized) {
	if (getCurrentUser()) {
		try {
			const response = await PublicAPI.getShare(to.params.token as string)
			// if the user is logged in, we diretly route to
			// the internal vote page
			return {
				name: 'vote',
				params: {
					id: response.data.share.pollId,
				},
			}
		} catch (error) {
			// in case of an error, reroute to the not found page
			return {
				name: 'notfound',
			}
		}
	}

	// continue for external users
	try {
		// first validate the existance of the public token
		await PublicAPI.getShare(to.params.token as string)
	} catch (error) {
		// in case of an error, reroute to the login page
		window.location.replace(generateUrl('login'))
	}

	// then look for an existing personal token from
	// the user's client stored cookie
	// matching the public token
	const personalToken = getCookieValue(to.params.token as string)

	if (personalToken && personalToken !== to.params.token) {
		// participant has already access to the poll and a private token
		// extend expiry time for 30 days after successful access
		const cookieExpiration = 30 * 24 * 60 * 1000
		setCookie(<string>to.params.token, personalToken, cookieExpiration)

		// reroute to the public vote page using the personal token
		return {
			name: 'publicVote',
			params: {
				token: personalToken,
			},
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
		redirect: {
			name: 'list',
			params: {
				type: FilterType.Relevant,
			},
		},
		name: 'root',
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
		const pollStore = usePollStore()
		const pollsStore = usePollsStore()
		const sharesStore = useSharesStore()

		const cheapLoading =
			sessionStore.watcher.mode !== 'noPolling'
			&& sessionStore.watcher.status !== 'stopped'
			&& to.name === from.name

		// first load app context -> session and preferences
		// await loading until further execution to ensure,
		// the context is loaded properly
		try {
			await loadContext(to, cheapLoading)
		} catch (error) {
			Logger.error('Could not load context')

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

		try {
			// for public pages we need to load the share first
			if (to.meta.publicPage) {
				await sessionStore.loadShare()
			}

			// vote pages load the particular poll
			// or reset the poll store if not a vote page
			if (to.meta.votePage) {
				pollStore.load()
			} else {
				pollStore.resetPoll()
			}

			// load polls at least for navigation
			if (!to.meta.publicPage && !cheapLoading) {
				await pollsStore.load()
			}

			// group pages need shares for the current poll group
			if (to.meta.groupPage) {
				sharesStore.load('pollGroup')
			}
		} catch (error) {
			Logger.warn('Could not load poll', { error })
			return {
				name: 'notfound',
			}
		}
	},
)

export { router }
