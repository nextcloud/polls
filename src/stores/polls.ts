/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import orderBy from 'lodash/orderBy'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'

import { Logger } from '../helpers/modules/logger'
import { PollsAPI } from '../Api'

import { useSessionStore } from './session'
import { usePollGroupsStore } from './pollGroups'

import type { AxiosError } from '@nextcloud/axios'
import type { Poll } from './poll.types'
import type {
	PollCategory,
	PollCategoryList,
	PollsStore,
	FilterType,
	SortType,
} from './polls.types'

export const sortColumnsMapping: { [key in SortType]: string } = {
	created: 'status.created',
	title: 'configuration.title',
	access: 'configuration.access',
	owner: 'owner.displayName',
	expire: 'configuration.expire',
	interaction: 'status.lastInteraction',
}

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
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived
			&& DateTime.fromSeconds(poll.status.relevantThreshold).diffNow('days')
				.days > -100
			&& (poll.currentUserStatus.isInvolved
				|| (poll.permissions.view && poll.configuration.access !== 'open')),
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
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.currentUserStatus.isOwner,
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
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived
			&& poll.permissions.view
			&& poll.configuration.access === 'private',
	},
	participated: {
		id: 'participated',
		title: t('polls', 'Participated'),
		titleExt: t('polls', 'Participated'),
		description: t('polls', 'All polls in which you participated.'),
		pinned: false,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.currentUserStatus.countVotes > 0,
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
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.configuration.access === 'open',
	},
	all: {
		id: 'all',
		title: t('polls', 'All polls'),
		titleExt: t('polls', 'All polls'),
		description: t('polls', 'All polls, where you have access to.'),
		pinned: false,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.permissions.view,
	},
	closed: {
		id: 'closed',
		title: t('polls', 'Closed polls'),
		titleExt: t('polls', 'Closed polls'),
		description: t('polls', 'All closed polls, where voting is disabled.'),
		pinned: false,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived
			&& poll.status.isExpired
			&& poll.permissions.view,
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
		filterCondition: (poll: Poll) =>
			poll.status.isArchived && poll.permissions.view,
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
		filterCondition: (poll: Poll) => {
			const sessionStore = useSessionStore()
			return sessionStore.currentUser.id !== poll.owner.id
		},
	},
}

export const usePollsStore = defineStore('polls', {
	state: (): PollsStore => ({
		polls: [],
		meta: {
			chunks: {
				size: 20,
				loaded: 1,
			},
			maxPollsInNavigation: 6,
			status: '',
		},
		sort: {
			by: 'created',
			reverse: true,
		},
		status: {
			loadingGroups: false,
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
		 * Sliced filtered and sorted polls for navigation
		 */
		navigationList:
			(state: PollsStore) =>
			(filterId: FilterType): Poll[] =>
				orderBy(
					state.polls.filter((poll: Poll) =>
						state.categories[filterId].filterCondition(poll),
					) ?? [],
					['created'],
					['desc'],
				).slice(0, state.meta.maxPollsInNavigation),

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

		/*
		 * polls list, filtered by current category and sorted
		 */
		pollsFilteredSorted(state: PollsStore): Poll[] {
			const sessionStore = useSessionStore()
			const pollGroupsStore = usePollGroupsStore()

			// if we are in a group route, return the polls of the current group
			if (sessionStore.route.name === 'group') {
				return pollGroupsStore.pollsInCurrendPollGroup
			}

			return orderBy(
				state.polls.filter((poll: Poll) =>
					this.currentCategory?.filterCondition(poll),
				) ?? [],
				[sortColumnsMapping[state.sort.by]],
				[state.sort.reverse ? 'desc' : 'asc'],
			)
		},

		/*
		 * Chunked filtered and sorted polls for main view
		 */
		chunkedList(): Poll[] {
			return this.pollsFilteredSorted.slice(0, this.loaded)
		},

		pollsCount(state: PollsStore): { [key: string]: number } {
			const count: Record<FilterType, number> = {} as Record<
				FilterType,
				number
			>

			for (const [key, category] of Object.entries(state.categories)) {
				count[key as FilterType] = state.polls.filter((poll: Poll) =>
					category.filterCondition(poll),
				).length
			}

			return count
		},

		/*
		 * Sliced filtered and sorted polls for dashboard
		 */
		dashboardList(state: PollsStore): Poll[] {
			return orderBy(
				state.polls.filter((poll: Poll) =>
					state.categories.relevant.filterCondition(poll),
				),
				['created'],
				['desc'],
			).slice(0, 7)
		},

		loaded(state: PollsStore): number {
			return state.meta.chunks.loaded * state.meta.chunks.size
		},

		datePolls(state: PollsStore): Poll[] {
			return state.polls.filter(
				(poll: Poll) => poll.type === 'datePoll' && !poll.status.isArchived,
			)
		},

		pollsLoading(state): boolean {
			return state.meta.status === 'loading'
		},

		countByCategory: (state: PollsStore) => (filterId: FilterType) =>
			state.polls.filter((poll: Poll) =>
				state.categories[filterId].filterCondition(poll),
			).length,
	},

	actions: {
		/**
		 * Load all polls and poll groups from the API.
		 * This will set the `polls` and `pollGroups` state properties.
		 *
		 * This will also set the `meta.status` to `Loading` while the request is in progress,
		 * and to `Loaded` or `Error` when the request is finished.
		 *
		 * @param {boolean} forced - If false, loading polls will only be done, when the status is not `Loaded`.
		 * @throws {Error} If the request fails and is not canceled.
		 * @return {Promise<void>}
		 */
		async load(forced: boolean = true): Promise<void> {
			const sessionStore = useSessionStore()

			if (!sessionStore.userStatus.isLoggedin) {
				this.polls = []
				this.meta.status = ''
				return
			}

			if (
				this.meta.status === 'loading'
				|| (!forced && this.meta.status === 'loaded')
			) {
				Logger.debug('Polls already loaded or loading, skipping load', {
					status: this.meta.status,
					forced,
				})
				return
			}

			this.meta.status = 'loading'

			const pollGroupsStore = usePollGroupsStore()

			try {
				const response = await PollsAPI.getPolls()
				this.polls = response.data.polls
				pollGroupsStore.pollGroups = response.data.pollGroups
				this.meta.status = 'loaded'
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.meta.status = 'error'
				Logger.error('Error loading polls', { error })
				throw error
			}
		},

		/**
		 * Sliced filtered and sorted polls for navigation
		 * @param filterList - List of poll IDs to filter by
		 */
		groupList(filterList: number[]): Poll[] {
			const pollsStore = usePollsStore()
			return orderBy(
				pollsStore.polls.filter((poll: Poll) => filterList.includes(poll.id))
					?? [],
				['created'],
				['desc'],
			).slice(0, pollsStore.meta.maxPollsInNavigation)
		},

		addOrUpdatePollGroupInList(payload: { poll: Poll }) {
			this.polls = this.polls
				.filter((p) => p.id !== payload.poll?.id)
				.concat(payload.poll)
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

		addChunk(): void {
			this.meta.chunks.loaded = this.meta.chunks.loaded + 1
		},

		resetChunks(): void {
			this.meta.chunks.loaded = 1
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
