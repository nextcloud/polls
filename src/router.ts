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
import { getCookieValue, setCookie } from './helpers/modules/cookieHelper'
import { Logger } from './helpers/modules/logger'
import { loadContext } from './composables/context'
import { emit } from '@nextcloud/event-bus'
import { Event } from './Types'

import Navigation from './views/Navigation.vue'

import { useSessionStore } from './stores/session'
import { usePollStore } from './stores/poll'
import { usePollsStore } from './stores/polls'
import { useVotesStore } from './stores/votes'
import { useOptionsStore } from './stores/options'
import { useSubscriptionStore } from './stores/subscription'

async function validateToken(to: RouteLocationNormalized) {
	const sessionStore = useSessionStore()

	// if the user is logged in, reroute to the vote page
	if (getCurrentUser()) {
		return {
			name: 'vote',
			params: {
				id: sessionStore.share.pollId,
			},
		}
	}

	// Check, if user has a personal token from the user's client stored cookie
	// matching the public token
	if (sessionStore.share.type === 'public') {
		const personalToken = getCookieValue(to.params.token as string)

		if (personalToken) {
			// extend expiry time for 30 days after successful access
			const cookieExpiration = 30 * 24 * 60 * 1000
			setCookie(to.params.token as string, personalToken, cookieExpiration)
			// participant has already access to the poll and a private token
			// reroute to the public vote page using the personal token
			return {
				name: 'publicVote',
				params: {
					token: personalToken,
				},
			}
		}
	}

	return true
}

const Combo = () => import('./views/Combo.vue')
const Forbidden = () => import('./views/Forbidden.vue')
const List = () => import('./views/PollList.vue')
const NotFound = () => import('./views/NotFound.vue')
const Vote = () => import('./views/Vote.vue')
const PublicRegisterView = () => import('./views/PublicRegisterView.vue')

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
		name: 'publicRegister',
		path: '/s/:token/register',
		components: {
			default: PublicRegisterView,
		},
		beforeEnter: validateToken,
		props: true,
		meta: {
			publicPage: true,
			registerPage: true,
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
		const pollStore = usePollStore()
		const votesStore = useVotesStore()
		const optionsStore = useOptionsStore()
		const subscriptionStore = useSubscriptionStore()

		sessionStore.navigationStatus = 'loading'
		emit(Event.TransitionsOff, 0)

		if (from.meta.votePage) {
			pollStore.$reset()
			votesStore.$reset()
			optionsStore.$reset()
			subscriptionStore.$reset()
		}

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

router.beforeResolve(async (to: RouteLocationNormalized) => {
	const sessionStore = useSessionStore()
	const watcherActive =
		sessionStore.watcher.mode !== 'noPolling'
		&& sessionStore.watcher.status !== 'stopped'

	if (to.meta.listPage) {
		const pollsStore = usePollsStore()
		await pollsStore.load(!watcherActive)
	}

	if (to.meta.registerPage) {
		const pollStore = usePollStore()

		try {
			await pollStore.load()
		} catch {
			return { name: 'notfound' }
		}

		// If the user no longer needs to register, redirect to the vote page
		const needsRegistration =
			pollStore.currentUserStatus.userRole === 'public'
			&& !pollStore.isClosed
			&& !pollStore.currentUserStatus.isLocked

		if (!needsRegistration) {
			return {
				name: 'publicVote',
				params: { token: to.params.token },
				replace: true,
			}
		}
	}

	if (to.meta.votePage) {
		const pollStore = usePollStore()
		const votesStore = useVotesStore()
		const optionsStore = useOptionsStore()
		const subscriptionStore = useSubscriptionStore()

		try {
			await pollStore.load()
		} catch {
			return { name: 'notfound' }
		}

		// Redirect unregistered public users to the registration page
		if (to.name === 'publicVote') {
			const needsRegistration =
				pollStore.currentUserStatus.userRole === 'public'
				&& !pollStore.isClosed
				&& !pollStore.currentUserStatus.isLocked

			if (needsRegistration) {
				return {
					name: 'publicRegister',
					params: { token: to.params.token },
				}
			}
		}

		await Promise.allSettled([
			votesStore.load(),
			optionsStore.load(),
			subscriptionStore.load(),
		])
		Logger.debug('Vote page data loaded', {
			session: sessionStore.currentUser,
			poll: pollStore,
			votes: votesStore.votes,
			options: optionsStore.options,
		})
	}
})

router.afterEach(() => {
	const sessionStore = useSessionStore()
	sessionStore.navigationStatus = 'idle'
	emit(Event.TransitionsOn, null)
})

export { router }
