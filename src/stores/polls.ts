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
import { StatusResults, User } from '../Types/index.ts'
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
export type PollGroup = {
	id: number
	created: number
	deleted: number
	description: string
	owner: User
	title: string
	titleExt: string
	pollIds: number[]
	slug: string
	allowEdit: boolean
}

export type PollCategoryList = Record<FilterType, PollCategory>

export type Meta = {
	chunksize: number
	loadedChunks: number
	maxPollsInNavigation: number
	status: StatusResults
}

export type PollList = {
	polls: Poll[]
	pollGroups: PollGroup[]
	meta: Meta
	sort: {
		by: SortType
		reverse: boolean
	}
	status: {
		loadingGroups: boolean
	}
	categories: PollCategoryList
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

export const pollCategories: PollCategoryList = {
	[FilterType.Relevant]: {
		id: FilterType.Relevant,
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
		filterCondition: (poll: Poll) => !poll.permissions.view,
	},
}

export const usePollsStore = defineStore('polls', {
	state: (): PollList => ({
		polls: [],
		pollGroups: [],
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
		status: {
			loadingGroups: false,
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
					state.polls.filter((poll: Poll) =>
						state.categories[filterId].filterCondition(poll),
					) ?? [],
					[SortType.Created],
					[SortDirection.Desc],
				).slice(0, state.meta.maxPollsInNavigation),

		/*
		 * Sliced filtered and sorted polls for navigation
		 */
		groupList:
			(state: PollList) =>
			(filterList: number[]): Poll[] =>
				orderBy(
					state.polls.filter((poll: Poll) => filterList.includes(poll.id))
						?? [],
					[SortType.Created],
					[SortDirection.Desc],
				).slice(0, state.meta.maxPollsInNavigation),

		pollGroupsSorted(state: PollList): PollGroup[] {
			return orderBy(
				state.pollGroups.filter(
					(group) => this.countPollsinPollGroups[group.id] > 0,
				),
				['title'],
				['asc'],
			)
		},

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

		currentPollGroup(state: PollList): PollGroup | undefined {
			const sessionStore = useSessionStore()
			if (sessionStore.route.name === 'group') {
				return state.pollGroups.find(
					(group) => group.slug === sessionStore.route.params.slug,
				)
			}
			return undefined
		},

		/*
		 * Count of polls in each poll group and return pollgroupid and count as list
		 * with the pollgroupid as key and the count as value
		 */

		countPollsinPollGroups(state: PollList): Record<number, number> {
			const counts: Record<number, number> = {}
			state.pollGroups.forEach((group) => {
				counts[group.id] = state.polls.filter((poll) =>
					group.pollIds.includes(poll.id),
				).length
			})
			return counts
		},

		groupPolls(state: PollList): Poll[] {
			if (!this.currentPollGroup) {
				return []
			}
			return state.polls.filter((poll) =>
				this.currentPollGroup?.pollIds.includes(poll.id),
			)
		},

		/*
		 * polls list, filtered by current category and sorted
		 */
		pollsFilteredSorted(state: PollList): Poll[] {
			const sessionStore = useSessionStore()
			if (sessionStore.route.name === 'group') {
				return this.groupPolls
			}

			return orderBy(
				state.polls.filter((poll: Poll) =>
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
				count[key as FilterType] = state.polls.filter((poll: Poll) =>
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
				state.polls.filter((poll: Poll) =>
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
			return state.polls.filter(
				(poll: Poll) =>
					poll.type === PollType.Date && !poll.status.isArchived,
			)
		},

		pollsLoading(state): boolean {
			return state.meta.status === StatusResults.Loading
		},

		countByCategory: (state: PollList) => (filterId: FilterType) =>
			state.polls.filter((poll: Poll) =>
				state.categories[filterId].filterCondition(poll),
			).length,
	},

	actions: {
		async load(): Promise<void> {
			this.meta.status = StatusResults.Loading
			try {
				const response = await PollsAPI.getPolls()
				this.polls = response.data.polls
				this.pollGroups = response.data.pollGroups
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

		addablePollGroups(pollId: number): PollGroup[] {
			return this.pollGroups.filter((group) => !group.pollIds.includes(pollId))
		},

		updatePollGroupElement(payload: { pollGroup: PollGroup; poll?: Poll }) {
			this.pollGroups = this.pollGroups
				.filter((g) => g.id !== payload.pollGroup.id)
				.concat(payload.pollGroup)

			if (payload.poll) {
				this.polls = this.polls
					.filter((p) => p.id !== payload.poll?.id)
					.concat(payload.poll)
			}
		},

		async addPollToPollGroup(payload: {
			pollId: number
			pollGroupId?: number
			groupTitle?: string
		}) {
			try {
				if (payload.pollGroupId) {
					const response = await PollsAPI.addPollToGroup(
						payload.pollGroupId,
						payload.pollId,
					)
					this.updatePollGroupElement({
						poll: response.data.poll,
						pollGroup: response.data.pollGroup,
					})
					return
				}

				if (payload.groupTitle) {
					const response = await PollsAPI.createPollGroupForPoll(
						payload.groupTitle,
						payload.pollId,
					)
					this.updatePollGroupElement({
						poll: response.data.poll,
						pollGroup: response.data.pollGroup,
					})
				}
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error adding poll to group', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async removePollFromGroup(payload: { pollGroupId: number; pollId: number }) {
			try {
				const response = await PollsAPI.removePollFromGroup(
					payload.pollGroupId,
					payload.pollId,
				)

				if (response.data.pollGroup === null) {
					// If the poll group was removed (=== null), remove it from the store
					this.pollGroups = this.pollGroups.filter(
						(group) => group.id !== payload.pollGroupId,
					)
					return
				}

				// update the collections
				this.updatePollGroupElement({
					poll: response.data.poll,
					pollGroup: response.data.pollGroup,
				})
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error removing poll from group', {
					error,
					payload,
				})
				throw error
			} finally {
				this.load()
			}
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

		getPollGroupName(PollGroupId: number): string {
			const group = this.pollGroups.find((group) => group.id === PollGroupId)
			if (group) {
				return group.title
			}
			return t('polls', 'Invalid Group ID')
		},

		async updatePollGroup(payload: {
			title: string
			titleExt: string
			description: string
			slug: string
		}) {
			if (!this.currentPollGroup) {
				throw new Error('No current poll group set')
			}
			try {
				const response = await PollsAPI.updatePollGroup(
					this.currentPollGroup.id,
					payload.title,
					payload.titleExt,
					payload.description,
				)
				this.updatePollGroupElement({
					pollGroup: response.data.pollGroup,
				})
				return response.data.pollGroup
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error updating poll group', {
					error,
					payload,
				})
				throw error
			}
		},
	},
})
