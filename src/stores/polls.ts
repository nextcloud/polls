/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { toRaw } from 'vue'
import { t } from '@nextcloud/l10n'

import { Logger } from '../helpers'
import { PollsAPI } from '../Api'

import { useSessionStore } from './session'
import { usePollGroupsStore } from './pollGroups'

import type { AxiosError } from '@nextcloud/axios'
import type { Poll } from './poll.types'
import type {
	FilterType,
	PaginatedPolls,
	PollCategory,
	PollCategoryList,
	PollListQuery,
	PollsStore,
	SortType,
} from './polls.types'

// Must match PollService::MAX_PAGE_SIZE
const MAX_PAGE_SIZE = 100
const DASHBOARD_POLLS = 7

// running meta request, shared by callers that do not force a reload
let metaRequest: Promise<void> | null = null

export const sortTitlesMapping: { [key in SortType]: string } = {
	created: t('polls', 'Created'),
	title: t('polls', 'Title'),
	access: t('polls', 'Access'),
	owner: t('polls', 'Owner'),
	expire: t('polls', 'Expire'),
	interaction: t('polls', 'Last interaction'),
}

const pollCategories: PollCategoryList = {
	relevant: {
		id: 'relevant',
		title: t('polls', 'Relevant'),
		titleExt: t('polls', 'Relevant polls'),
		description: t(
			'polls',
			'Relevant polls which are relevant to you, because you are a participant, the owner or you are invited. Only polls not older than 100 days compared to creation, last interaction, expiration or latest option (for date polls) are shown.',
		),
		pinned: false,
		showInNavigation: () => true,
	},
	my: {
		id: 'my',
		title: t('polls', 'My polls'),
		titleExt: t('polls', 'My polls'),
		description: t('polls', 'These are all polls where you are the owner.'),
		pinned: false,
		showInNavigation: () => {
			const sessionStore = useSessionStore()
			return sessionStore.appPermissions.pollCreation
		},
	},
	private: {
		id: 'private',
		title: t('polls', 'Private polls'),
		titleExt: t('polls', 'Private polls'),
		description: t('polls', 'All private polls, to which you have access.'),
		pinned: false,
		showInNavigation: () => {
			const sessionStore = useSessionStore()
			return sessionStore.appPermissions.pollCreation
		},
	},
	participated: {
		id: 'participated',
		title: t('polls', 'Participated'),
		titleExt: t('polls', 'Participated'),
		description: t('polls', 'All polls in which you participated.'),
		pinned: false,
		showInNavigation: () => true,
	},
	open: {
		id: 'open',
		title: t('polls', 'Openly accessible polls'),
		titleExt: t('polls', 'Openly accessible polls'),
		description: t(
			'polls',
			'A complete list with all openly accessible polls on this site.',
		),
		pinned: false,
		showInNavigation: () => {
			const sessionStore = useSessionStore()
			return sessionStore.appPermissions.pollCreation
		},
	},
	all: {
		id: 'all',
		title: t('polls', 'All polls'),
		titleExt: t('polls', 'All polls'),
		description: t('polls', 'All polls, where you have access to.'),
		pinned: false,
		showInNavigation: () => true,
	},
	closed: {
		id: 'closed',
		title: t('polls', 'Closed polls'),
		titleExt: t('polls', 'Closed polls'),
		description: t('polls', 'All closed polls, where voting is disabled.'),
		pinned: false,
		showInNavigation: () => true,
	},
	archived: {
		id: 'archived',
		title: t('polls', 'Archive'),
		titleExt: t('polls', 'My archived polls'),
		description: t('polls', 'Your archived polls are only accessible to you.'),
		pinned: true,
		showInNavigation: () => {
			const sessionStore = useSessionStore()
			return sessionStore.appPermissions.pollCreation
		},
	},
	admin: {
		id: 'admin',
		title: t('polls', 'Administration'),
		titleExt: t('polls', 'Administrative access'),
		description: t(
			'polls',
			'You can delete, archive and take over polls in this list, but access is still not possible.',
		),
		pinned: true,
		showInNavigation: () => {
			const sessionStore = useSessionStore()
			return !!sessionStore.currentUser?.isAdmin
		},
	},
}

// newest load per paginated list, superseded loads must not write their result
const loadGenerations = new WeakMap<PaginatedPolls, number>()

/**
 * Fetch polls into a paginated list
 *
 * A limit bigger than MAX_PAGE_SIZE is requested in consecutive pages, so
 * reloading a list, which was scrolled beyond the maximum page size, keeps
 * all polls, which were loaded before.
 *
 * A newer load into the same list supersedes a running one, which then stops
 * requesting pages and discards its result. The API methods share one cancel
 * token per endpoint, so without this the next page of an outdated load would
 * cancel the request of the newer one.
 *
 * @param target list to fill
 * @param fetchPage request for one page, must not read mutable store state
 * @param limit number of polls to load
 * @param append append to the loaded polls instead of replacing them
 */
async function fetchInto(
	target: PaginatedPolls,
	fetchPage: (
		offset: number,
		limit: number,
	) => Promise<{ data: { polls: Poll[]; total: number } }>,
	limit: number,
	append: boolean,
): Promise<void> {
	const rawTarget = toRaw(target)
	const generation = (loadGenerations.get(rawTarget) ?? 0) + 1
	loadGenerations.set(rawTarget, generation)

	const offset = append ? target.polls.length : 0
	target.status = 'loading'
	try {
		const polls: Poll[] = []
		let total = 0

		do {
			const response = await fetchPage(
				offset + polls.length,
				Math.min(limit - polls.length, MAX_PAGE_SIZE),
			)
			if (loadGenerations.get(rawTarget) !== generation) {
				// a newer load took over this list
				return
			}
			total = response.data.total
			if (response.data.polls.length === 0) {
				// the list shrunk since the first page was requested
				break
			}
			polls.push(...response.data.polls)
		} while (polls.length < limit && offset + polls.length < total)

		target.polls = append ? target.polls.concat(polls) : polls
		target.total = total
		target.status = 'loaded'
	} catch (error) {
		if (
			(error as AxiosError)?.code === 'ERR_CANCELED'
			|| loadGenerations.get(rawTarget) !== generation
		) {
			return
		}
		target.status = 'error'
		Logger.error('Error loading polls', { error })
		throw error
	}
}

export const usePollsStore = defineStore('polls', {
	state: (): PollsStore => ({
		list: { polls: [], total: 0, status: '' },
		datePolls: { polls: [], total: 0, status: '' },
		listMeta: {
			counts: {
				relevant: 0,
				my: 0,
				private: 0,
				participated: 0,
				open: 0,
				all: 0,
				closed: 0,
				archived: 0,
				admin: 0,
			},
			pollGroupCounts: {},
			status: '',
		},
		navigationPolls: {},
		meta: {
			pageSize: 20,
			maxPollsInNavigation: 6,
		},
		sort: {
			by: 'created',
			reverse: true,
		},
		categories: pollCategories,
	}),

	getters: {
		navigationCategories(state: PollsStore): PollCategory[] {
			return Object.values(state.categories).filter((category) =>
				category.showInNavigation(),
			)
		},

		/*
		 * Newest polls of a category or poll group, loaded on expanding the navigation entry
		 */
		navigationList:
			(state: PollsStore) =>
			(key: FilterType | number): Poll[] =>
				state.navigationPolls[key] ?? [],

		currentCategory(state: PollsStore): PollCategory {
			const sessionStore = useSessionStore()

			if (
				sessionStore.route.name === 'list'
				&& sessionStore.route.params.type
			) {
				return state.categories[sessionStore.route.params.type as FilterType]
			}
			return state.categories.relevant
		},

		pollsCount(state: PollsStore): Record<FilterType, number> {
			return state.listMeta.counts
		},

		/*
		 * Server side filter for the current route (category or poll group)
		 */
		listFilter(): Pick<PollListQuery, 'category' | 'pollGroup'> {
			const sessionStore = useSessionStore()
			const pollGroupsStore = usePollGroupsStore()

			if (sessionStore.route.name === 'group') {
				// -1 matches no poll group, if the slug is unknown
				return { pollGroup: pollGroupsStore.currentPollGroup?.id ?? -1 }
			}
			return { category: this.currentCategory.id }
		},

		/*
		 * Server side filter and sorting of the current route
		 */
		listQuery(): Omit<PollListQuery, 'offset' | 'limit'> {
			return {
				...this.listFilter,
				sortBy: this.sort.by,
				sortDirection: this.sort.reverse ? 'desc' : 'asc',
			}
		},

		dashboardList(state: PollsStore): Poll[] {
			return state.list.polls
		},

		pollsLoading(state: PollsStore): boolean {
			return state.list.status === 'loading'
		},

		hasMore(state: PollsStore): boolean {
			return state.list.polls.length < state.list.total
		},

		hasMoreDatePolls(state: PollsStore): boolean {
			return state.datePolls.polls.length < state.datePolls.total
		},
	},

	actions: {
		/**
		 * Load the poll counts and the poll groups for the navigation.
		 * Does not load the polls lists.
		 *
		 * @param {boolean} forced - If false, reuse loaded data or a running request
		 */
		async loadMeta(forced: boolean = true): Promise<void> {
			if (!forced && (metaRequest || this.listMeta.status === 'loaded')) {
				return metaRequest ?? undefined
			}

			const pollGroupsStore = usePollGroupsStore()
			this.listMeta.status = 'loading'

			const request = (async () => {
				try {
					const response = await PollsAPI.getPollsMeta()
					this.listMeta = {
						counts: response.data.counts,
						// php returns empty maps as arrays
						pollGroupCounts: { ...response.data.pollGroupCounts },
						status: 'loaded',
					}
					pollGroupsStore.pollGroups = response.data.pollGroups
				} catch (error) {
					if ((error as AxiosError)?.code === 'ERR_CANCELED') {
						return
					}
					this.listMeta.status = 'error'
					Logger.error('Error loading poll list meta data', { error })
					throw error
				}
			})()

			metaRequest = request
			try {
				await request
			} finally {
				if (metaRequest === request) {
					metaRequest = null
				}
			}
		},

		/**
		 * Load the first page of the current list (category or poll group)
		 * Previously loaded polls are replaced.
		 *
		 * @param {number} limit - Number of polls to load
		 */
		async loadList(limit?: number): Promise<void> {
			const sessionStore = useSessionStore()
			const pollGroupsStore = usePollGroupsStore()

			// poll groups are needed to resolve the slug of the group route
			if (
				sessionStore.route.name === 'group'
				&& !pollGroupsStore.currentPollGroup
			) {
				const metaWasLoaded = this.listMeta.status === 'loaded'
				await this.loadMeta(false)

				// the slug is not part of the cached poll groups, e.g. because the
				// group was created in another session, so refresh them once
				if (metaWasLoaded && !pollGroupsStore.currentPollGroup) {
					await this.loadMeta(true)
				}
			}

			// pinned, so every page of this load uses the same filter and sorting,
			// even if the route or the sorting changes in between
			const query = this.listQuery
			await fetchInto(
				this.list,
				(offset, limit) => PollsAPI.getPolls({ ...query, offset, limit }),
				limit ?? this.meta.pageSize,
				false,
			)
		},

		/**
		 * Append the next page to the current list
		 */
		async loadMore(): Promise<void> {
			if (this.list.status === 'loading' || !this.hasMore) {
				return
			}
			const query = this.listQuery
			await fetchInto(
				this.list,
				(offset, limit) => PollsAPI.getPolls({ ...query, offset, limit }),
				this.meta.pageSize,
				true,
			)
		},

		/**
		 * Load the newest relevant polls for the dashboard widget
		 */
		async loadDashboard(): Promise<void> {
			await fetchInto(
				this.list,
				(offset, limit) =>
					PollsAPI.getPolls({
						category: 'relevant',
						sortBy: 'created',
						sortDirection: 'desc',
						offset,
						limit,
					}),
				DASHBOARD_POLLS,
				false,
			)
		},

		/**
		 * Load the first page of the non archived date polls
		 *
		 * @param {number} limit - Number of polls to load
		 */
		async loadDatePolls(limit?: number): Promise<void> {
			await fetchInto(
				this.datePolls,
				(offset, limit) => PollsAPI.getDatePolls(offset, limit),
				limit ?? this.meta.pageSize,
				false,
			)
		},

		async loadMoreDatePolls(): Promise<void> {
			if (this.datePolls.status === 'loading' || !this.hasMoreDatePolls) {
				return
			}
			await fetchInto(
				this.datePolls,
				(offset, limit) => PollsAPI.getDatePolls(offset, limit),
				this.meta.pageSize,
				true,
			)
		},

		/**
		 * Load the newest polls of a category or poll group for its navigation entry
		 *
		 * @param {FilterType | number} key - Category id or poll group id
		 */
		async loadNavigationList(key: FilterType | number): Promise<void> {
			try {
				const response = await PollsAPI.getNavigationPolls({
					...(typeof key === 'number'
						? { pollGroup: key }
						: { category: key }),
					sortBy: 'created',
					sortDirection: 'desc',
					offset: 0,
					limit: this.meta.maxPollsInNavigation,
				})
				this.navigationPolls[key] = response.data.polls
			} catch (error) {
				Logger.error('Error loading navigation polls', { error, key })
				throw error
			}
		},

		/**
		 * Refresh everything that was loaded before, after polls changed.
		 * Lists keep the number of already loaded polls.
		 */
		async load(): Promise<void> {
			const requests: Promise<void>[] = [
				this.loadMeta(),
				// refresh already expanded navigation entries, object keys are strings
				...Object.keys(this.navigationPolls).map((key) =>
					this.loadNavigationList(
						/^\d+$/.test(key) ? Number(key) : (key as FilterType),
					),
				),
			]
			if (this.list.status !== '') {
				requests.push(
					this.loadList(
						Math.max(this.list.polls.length, this.meta.pageSize),
					),
				)
			}
			if (this.datePolls.status !== '') {
				requests.push(
					this.loadDatePolls(
						Math.max(this.datePolls.polls.length, this.meta.pageSize),
					),
				)
			}
			await Promise.all(requests)
		},

		/**
		 * Update a poll after its poll groups changed
		 * and refresh the counts and the current list
		 *
		 * @param payload
		 * @param payload.poll
		 */
		async addOrUpdatePollGroupInList(payload: { poll: Poll }): Promise<void> {
			this.list.polls = this.list.polls.map((poll) =>
				poll.id === payload.poll.id ? payload.poll : poll,
			)
			await this.load()
		},

		async changeOwner(payload: { pollId: number; userId: string }) {
			try {
				await PollsAPI.changeOwner(payload.pollId, payload.userId)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error changing poll owner', {
					error,
					payload,
				})
				throw error
			} finally {
				this.load()
			}
		},

		async clone(payload: { pollId: number }): Promise<void> {
			try {
				await PollsAPI.clonePoll(payload.pollId)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error cloning poll', {
					error,
					payload,
				})
				throw error
			} finally {
				this.load()
			}
		},

		async delete(payload: { pollId: number }): Promise<void> {
			try {
				await PollsAPI.deletePoll(payload.pollId)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting poll', {
					error,
					payload,
				})
				throw error
			} finally {
				this.load()
			}
		},

		async toggleArchive(payload: { pollId: number }) {
			try {
				await PollsAPI.toggleArchive(payload.pollId)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error archiving/restoring poll', {
					error,
					payload,
				})
				throw error
			} finally {
				this.load()
			}
		},

		async takeOver(payload: { pollId: number }) {
			try {
				await PollsAPI.takeOver(payload.pollId)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error archiving/restoring poll', {
					error,
					payload,
				})
				throw error
			} finally {
				this.load()
			}
		},
	},
})
