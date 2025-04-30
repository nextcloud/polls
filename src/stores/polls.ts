/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import orderBy from 'lodash/orderBy'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'

import { Logger } from '../helpers/index.ts'
import { PollsAPI } from '../Api/index.ts'

import { AccessType, Poll, PollType } from './poll.ts'
import { useSessionStore } from './session.ts'
import { StatusResults } from '../Types/index.ts'
import { AxiosError } from '@nextcloud/axios'

export enum SortType {
	Created = 'created',
	Title = 'title',
	Access = 'access',
	Owner = 'owner',
	Expire = 'expire',
	Interaction = 'interaction',
}

export enum SortDirection {
	Asc = 'asc',
	Desc = 'desc',
}

export enum FilterType {
	Relevant = 'relevant',
	My = 'my',
	Private = 'private',
	Participated = 'participated',
	Open = 'open',
	All = 'all',
	Closed = 'closed',
	Archived = 'archived',
	Admin = 'admin',
}

export type PollCategory = {
	id: FilterType
	title: string
	titleExt: string
	description: string
	pinned: boolean
	showInNavigation(): boolean
	filterCondition(poll: Poll): boolean
}

export type PollCategorieList = Record<FilterType, PollCategory>

export type Meta = {
	chunksize: number
	loadedChunks: number
	maxPollsInNavigation: number
	status: StatusResults
}

export type PollList = {
	list: Poll[]
	meta: Meta
	sort: {
		by: SortType
		reverse: boolean
	}
	categories: PollCategorieList
}

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

export const pollCategories: PollCategorieList = {
	[FilterType.Relevant]: {
		id: FilterType.Relevant,
		title: t('polls', 'Relevant'),
		titleExt: t('polls', 'Relevant polls'),
		description: t(
			'polls',
			'Relevant polls which are relevant or for you, because you are a participant or the owner or you are invited to. Only polls not older than 100 days compared to creation, last interaction, expiration or latest option (for date polls) are shown.',
		),
		pinned: false,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived
			&& DateTime.fromSeconds(poll.status.relevantThreshold).diffNow('days')
				.days > -100
			&& (poll.currentUserStatus.isInvolved
				|| (poll.permissions.view
					&& poll.configuration.access !== AccessType.Open)),
	},
	[FilterType.My]: {
		id: FilterType.My,
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
	[FilterType.Private]: {
		id: FilterType.Private,
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
			&& poll.configuration.access === AccessType.Private,
	},
	[FilterType.Participated]: {
		id: FilterType.Participated,
		title: t('polls', 'Participated'),
		titleExt: t('polls', 'Participated'),
		description: t('polls', 'All polls in which you participated.'),
		pinned: false,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.currentUserStatus.countVotes > 0,
	},
	[FilterType.Open]: {
		id: FilterType.Open,
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
			!poll.status.isArchived && poll.configuration.access === AccessType.Open,
	},
	[FilterType.All]: {
		id: FilterType.All,
		title: t('polls', 'All polls'),
		titleExt: t('polls', 'All polls'),
		description: t('polls', 'All polls, where you have access to.'),
		pinned: false,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) => !poll.status.isArchived,
	},
	[FilterType.Closed]: {
		id: FilterType.Closed,
		title: t('polls', 'Closed polls'),
		titleExt: t('polls', 'Closed polls'),
		description: t('polls', 'All closed polls, where voting is disabled.'),
		pinned: false,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.status.isExpired,
	},
	[FilterType.Archived]: {
		id: FilterType.Archived,
		title: t('polls', 'Archive'),
		titleExt: t('polls', 'My archived polls'),
		description: t('polls', 'Your archived polls are only accessible to you.'),
		pinned: true,
		showInNavigation: () => {
			const sessionStore = useSessionStore()
			return sessionStore.appPermissions.pollCreation
		},
		filterCondition: (poll: Poll) => poll.status.isArchived,
	},
	[FilterType.Admin]: {
		id: FilterType.Admin,
		title: t('polls', 'Administration'),
		titleExt: t('polls', 'Administrative access.'),
		description: t(
			'polls',
			'You can delete, archive and take over polls in this list, but access is still not possible.',
		),
		pinned: true,
		showInNavigation: () => {
			const sessionStore = useSessionStore()
			return !!sessionStore.currentUser?.isAdmin
		},
		filterCondition: (poll: Poll) => !poll.permissions.view,
	},
}

export const usePollsStore = defineStore('polls', {
	state: (): PollList => ({
		list: [],
		meta: {
			chunksize: 20,
			loadedChunks: 1,
			maxPollsInNavigation: 6,
			status: StatusResults.Loaded,
		},
		sort: {
			by: SortType.Created,
			reverse: true,
		},
		categories: pollCategories,
	}),

	getters: {
		navigationCategories(state: PollList): PollCategory[] {
			return Object.values(state.categories).filter((category) =>
				category.showInNavigation(),
			)
		},

		/*
		 * Sliced filtered and sorted polls for navigation
		 */
		navigationList:
			(state: PollList) =>
			(filterId: FilterType): Poll[] =>
				orderBy(
					state.list.filter((poll: Poll) =>
						state.categories[filterId].filterCondition(poll),
					) ?? [],
					[SortType.Created],
					[SortDirection.Desc],
				).slice(0, state.meta.maxPollsInNavigation),

		currentCategory(state: PollList): PollCategory {
			const sessionStore = useSessionStore()

			if (
				sessionStore.route.name === 'list'
				&& sessionStore.route.params.type
			) {
				return state.categories[sessionStore.route.params.type as FilterType]
			}
			return state.categories[FilterType.Relevant]
		},

		/*
		 * polls list, filtered by current category and sorted
		 */
		pollsFilteredSorted(state: PollList): Poll[] {
			return orderBy(
				state.list.filter((poll: Poll) =>
					this.currentCategory?.filterCondition(poll),
				) ?? [],
				[sortColumnsMapping[state.sort.by]],
				[state.sort.reverse ? SortDirection.Desc : SortDirection.Asc],
			)
		},

		/*
		 * Chunked filtered and sorted polls for main view
		 */
		chunkedList(): Poll[] {
			return this.pollsFilteredSorted.slice(0, this.loaded)
		},

		pollsCount(state: PollList): { [key: string]: number } {
			const count: Record<FilterType, number> = {} as Record<
				FilterType,
				number
			>

			for (const [key, category] of Object.entries(state.categories)) {
				count[key as FilterType] = state.list.filter((poll: Poll) =>
					category.filterCondition(poll),
				).length
			}

			return count
		},

		/*
		 * Sliced filtered and sorted polls for dashboard
		 */
		dashboardList(state: PollList): Poll[] {
			return orderBy(
				state.list.filter((poll: Poll) =>
					state.categories[FilterType.Relevant].filterCondition(poll),
				),
				[SortType.Created],
				[SortDirection.Desc],
			).slice(0, 7)
		},

		loaded(state: PollList): number {
			return state.meta.loadedChunks * state.meta.chunksize
		},

		datePolls(state: PollList): Poll[] {
			return state.list.filter(
				(poll: Poll) =>
					poll.type === PollType.Date && !poll.status.isArchived,
			)
		},

		pollsLoading(state): boolean {
			return state.meta.status === StatusResults.Loading
		},

		countByCategory: (state: PollList) => (filterId: FilterType) =>
			state.list.filter((poll: Poll) =>
				state.categories[filterId].filterCondition(poll),
			).length,
	},

	actions: {
		async load(): Promise<void> {
			this.meta.status = StatusResults.Loading
			try {
				const response = await PollsAPI.getPolls()
				this.list = response.data.list
				this.meta.status = StatusResults.Loaded
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.meta.status = StatusResults.Error
				Logger.error('Error loading polls', { error })
				throw error
			}
		},

		addChunk(): void {
			this.meta.loadedChunks = this.meta.loadedChunks + 1
		},

		resetChunks(): void {
			this.meta.loadedChunks = 1
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

		async delete(payload: { pollId: number }) {
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
