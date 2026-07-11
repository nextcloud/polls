/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type {
	RouteLocationNormalized,
	RouteRecordRaw
} from 'vue-router';

import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { Settings } from 'luxon'
import {
	createRouter,
	createWebHistory,
} from 'vue-router'
import NavigationView from './views/NavigationView.vue'
import { getCookieValue, setCookie } from './helpers/modules/cookieHelper.ts'
import { Logger } from './helpers/modules/logger.ts'
import { activeRoute } from './routerState.ts'
import { useOptionsStore } from './stores/options.ts'
import { usePollStore } from './stores/poll.ts'
import { usePollsStore } from './stores/polls.ts'
import { usePreferencesStore } from './stores/preferences.ts'
import { useSessionStore } from './stores/session.ts'
import { useSubscriptionStore } from './stores/subscription.ts'
import { useVotesStore } from './stores/votes.ts'
import { Event } from './Types/index.ts'

declare module 'vue-router' {
	interface RouteMeta {
		comboPage?: boolean
		errorPage?: boolean
		groupPage?: boolean
		internalVotePage?: boolean
		listPage?: boolean
		publicPage?: boolean
		publicVotePage?: boolean
		registerPage?: boolean
		votePage?: boolean
	}
}

const ComboView = () => import('./views/ComboView.vue')
const ForbiddenView = () => import('./views/ForbiddenView.vue')
const List = () => import('./views/PollListView.vue')
const NotFoundView = () => import('./views/NotFoundView.vue')
const VoteView = () => import('./views/VoteView.vue')
const PublicRegisterView = () => import('./views/PublicRegisterView.vue')

const SideBarView = () => import('./views/SideBarView.vue')
const SideBarPollGroupView = () => import('./views/SideBarPollGroupView.vue')
const SideBarComboView = () => import('./views/SideBarComboView.vue')

const routes: RouteRecordRaw[] = [
	{
		name: 'list',
		path: '/list/:type?',
		components: {
			default: List,
			navigation: NavigationView,
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
			navigation: NavigationView,
			sidebar: SideBarPollGroupView,
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
			default: ComboView,
			navigation: NavigationView,
			sidebar: SideBarComboView,
		},
		meta: {
			comboPage: true,
		},
	},
	{
		name: 'notfound',
		path: '/not-found',
		components: {
			default: NotFoundView,
			navigation: NavigationView,
		},
		meta: {
			errorPage: true,
		},
	},
	{
		name: 'forbidden',
		path: '/forbidden',
		components: {
			default: ForbiddenView,
			navigation: NavigationView,
		},
		meta: {
			errorPage: true,
		},
	},
	{
		name: 'vote',
		path: '/vote/:id',
		components: {
			default: VoteView,
			navigation: NavigationView,
			sidebar: SideBarView,
		},
		props: true,
		meta: {
			votePage: true,
			internalVotePage: true,
		},
	},
	{
		name: 'publicVote',
		path: '/s/:token',
		components: {
			default: VoteView,
			sidebar: SideBarView,
		},
		beforeEnter: validateToken,
		props: true,
		meta: {
			publicPage: true,
			publicVotePage: true,
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

/**
 * Check if a registration is needed.
 * Registration is needed for public shares as long as the poll is not closed and the user is not locked.
 *
 * @param pollStore
 * @return boolean
 */
function needsRegistration(pollStore: ReturnType<typeof usePollStore>): boolean {
	return (
		pollStore.currentUserStatus.userRole === 'public'
		&& !pollStore.isClosed
		&& !pollStore.currentUserStatus.isLocked
	)
}

/**
 * Load poll list for the list page. If the watcher is active, we can skip
 * loading the list as it will be updated by the watcher.
 *
 * @param watcherActive Whether the watcher is active or not
 */
async function handleListPage(watcherActive: boolean): Promise<void> {
	await usePollsStore().load(!watcherActive)
}

/**
 * Load poll and options for the register page. If the poll doesn't exist or can't be loaded, redirect to not found page.
 * If the user doesn't need to register, redirect to the public vote page.
 *
 * @param to Requested route
 * @return RouteLocationNormalized | void
 */
async function handleRegisterPage(to: RouteLocationNormalized) {
	const pollStore = usePollStore()
	const optionsStore = useOptionsStore()

	try {
		await Promise.all([pollStore.load(), optionsStore.load()])
	} catch {
		return { name: 'notfound' }
	}

	// if the user doesn't need to register, directly redirect to the public vote page
	if (!needsRegistration(pollStore)) {
		return {
			name: 'publicVote',
			params: { token: to.params.token },
			replace: true,
		}
	}
}

/**
 * Load poll, options, votes and subscription for the vote page. If the poll
 * doesn't exist or can't be loaded, redirect to not found page.
 * If the user needs to register, redirect to the public register page.
 *
 * @param to Requested route
 * @return RouteLocationNormalized | void
 */
async function handleVotePage(to: RouteLocationNormalized) {
	const sessionStore = useSessionStore()
	const pollStore = usePollStore()
	const votesStore = useVotesStore()
	const optionsStore = useOptionsStore()
	const subscriptionStore = useSubscriptionStore()

	try {
		await pollStore.load()
	} catch {
		return { name: 'notfound' }
	}

	if (to.meta.publicVotePage && needsRegistration(pollStore)) {
		return { name: 'publicRegister', params: { token: to.params.token } }
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

const router = createRouter({
	history: createWebHistory(generateUrl('/apps/polls')),
	routes,
	linkActiveClass: 'active',
})

// Initialize activeRoute with the router's starting location.
activeRoute.value = router.currentRoute.value

router.beforeEach(
	async (to: RouteLocationNormalized, from: RouteLocationNormalized) => {
		activeRoute.value = to

		const sessionStore = useSessionStore()
		const pollStore = usePollStore()
		const votesStore = useVotesStore()
		const optionsStore = useOptionsStore()
		const subscriptionStore = useSubscriptionStore()
		const preferencesStore = usePreferencesStore()

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
		if (
			to.name === from.name
			&& sessionStore.watcher.mode !== 'noPolling'
			&& sessionStore.watcher.status !== 'stopped'
		) {
			// first load app context -> session and preferences
			Logger.info('Context loading skipped (cheap loading)')
			return
		}

		try {
			await sessionStore.loadSession()

			Settings.defaultLocale =
				sessionStore.currentUser.localeCodeIntl
				|| sessionStore.currentUser.languageCodeIntl

			if (sessionStore.userStatus.isLoggedin) {
				await preferencesStore.load()
			}

			Logger.info('Context loaded')
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
		return handleListPage(watcherActive)
	}
	if (to.meta.registerPage) {
		return handleRegisterPage(to)
	}
	if (to.meta.votePage) {
		return handleVotePage(to)
	}
})

router.afterEach(() => {
	const sessionStore = useSessionStore()
	sessionStore.navigationStatus = 'idle'
	emit(Event.TransitionsOn, null)
})

export { router }
