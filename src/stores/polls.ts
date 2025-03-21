/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import orderBy from 'lodash/orderBy'
import moment from '@nextcloud/moment'
import { t } from '@nextcloud/l10n'

import { Logger } from '../helpers/index.ts'
import { PollsAPI } from '../Api/index.js'

import { AccessType, Poll , PollType } from './poll.ts'
import { useSessionStore } from './session.ts'
import { StatusResults } from '../Types/index.ts'

export enum SortType {
	Created = 'created',
	Title = 'title',
	Access = 'access',
	Owner = 'owner',
	Expire = 'expire',
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
	createDependent: boolean
	filterCondition(poll: Poll): boolean
}

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
	categories: PollCategory[]
}

export const sortColumnsMapping: { [key in SortType]: string } = {
	created: 'status.created',
	title: 'configuration.title',
	access: 'configuration.access',
	owner: 'owner.displayName',
	expire: 'configuration.expire',
}

// const preferencesStore = usePreferencesStore()
const filterRelevantCondition = (poll) => !poll.status.deleted
	&& moment().diff(moment.unix(poll.status.relevantThreshold), 'days') < 100
	&& (poll.currentUserStatus.isInvolved || (poll.permissions.view && poll.configuration.access !== AccessType.Open))

const filterMyPolls = (poll) => !poll.status.deleted && poll.currentUserStatus.isOwner
const filterPrivatePolls = (poll) => !poll.status.deleted && poll.configuration.access === AccessType.Private
const filterParticipatedPolls = (poll) => !poll.status.deleted && poll.currentUserStatus.countVotes > 0
const filterOpenPolls = (poll) => !poll.status.deleted && poll.configuration.access === AccessType.Open
const filterAllPolls = (poll) => !poll.status.deleted
const filterClosedPolls = (poll) => !poll.status.deleted && poll.configuration.expire && moment.unix(poll.configuration.expire).diff() < 0
const filterArchivedPolls = (poll) => poll.status.deleted

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
		categories: [
			{
				id: FilterType.Relevant,
				title: t('polls', 'Relevant'),
				titleExt: t('polls', 'Relevant polls'),
				description: t('polls', 'Relevant polls which are relevant or for you, because you are a participant or the owner or you are invited to.'),
				pinned: false,
				createDependent: false,
				filterCondition: (poll) => filterRelevantCondition(poll),
			},
			{
				id: FilterType.My,
				title: t('polls', 'My polls'),
				titleExt: t('polls', 'My polls'),
				description: t('polls', 'Your polls (in which you are the owner).'),
				pinned: false,
				createDependent: true,
				filterCondition: (poll) => filterMyPolls(poll),
			},
			{
				id: FilterType.Private,
				title: t('polls', 'Private polls'),
				titleExt: t('polls', 'Private polls'),
				description: t('polls', 'All private polls, to which you have access.'),
				pinned: false,
				createDependent: true,
				filterCondition: (poll) => filterPrivatePolls(poll),
			},
			{
				id: FilterType.Participated,
				title: t('polls', 'Participated'),
				titleExt: t('polls', 'Participated'),
				description: t('polls', 'All polls, where you placed a vote.'),
				pinned: false,
				createDependent: false,
				filterCondition: (poll) => filterParticipatedPolls(poll),
			},
			{
				id: FilterType.Open,
				title: t('polls', 'Openly accessible polls'),
				titleExt: t('polls', 'Openly accessible polls'),
				description: t('polls', 'A complete list with all openly accessible polls on this site, regardless who is the owner.'),
				pinned: false,
				createDependent: true,
				filterCondition: (poll) => filterOpenPolls(poll),
			},
			{
				id: FilterType.All,
				title: t('polls', 'All polls'),
				titleExt: t('polls', 'All polls'),
				description: t('polls', 'All polls, where you have access to.'),
				pinned: false,
				createDependent: false,
				filterCondition: (poll) => filterAllPolls(poll),
			},
			{
				id: FilterType.Closed,
				title: t('polls', 'Closed polls'),
				titleExt: t('polls', 'Closed polls'),
				description: t('polls', 'All closed polls, where voting is disabled.'),
				pinned: false,
				createDependent: false,
				filterCondition: (poll) => filterClosedPolls(poll),
			},
			{
				id: FilterType.Archived,
				title: t('polls', 'Archive'),
				titleExt: t('polls', 'My archived polls'),
				description: t('polls', 'Your archived polls are only accessible to you.'),
				pinned: true,
				createDependent: true,
				filterCondition: (poll) => filterArchivedPolls(poll),
			},
		],
	}),

	getters: {
		navigationCategories(state: PollList): PollCategory[] {
			const sessionStore = useSessionStore()

			if (sessionStore.appPermissions.pollCreation) {
				return state.categories
			}
			return state.categories.filter((category) => (!category.createDependent))
		},

		currentCategory(state: PollList): PollCategory | null {
			const sessionStore = useSessionStore()

			if (sessionStore.route.name === 'list' && sessionStore.route.params.type) {
				return state.categories.find((category) => category.id === sessionStore.route.params.type)
			}
			return state.categories.find((category) => category.id === FilterType.Relevant)
		},

		/*
		* polls list, filtered by category
		*/
		pollsByCategory: (state: PollList) => (filterId: FilterType) => {
			const useCategory = state.categories.find((category) => category.id === filterId)
			return state.list.filter((poll) => useCategory.filterCondition(poll))
		},

		pollsCount(state: PollList): Array<{ id: string, count: number }> {
			const count = []
			for (const category of state.categories) {
				count[category.id] = state.list.filter((poll) => category.filterCondition(poll)).length
			}
			return count
		},
		/*
		* polls list, filtered by current category and sorted
		*/
		pollsFilteredSorted(state: PollList): Poll[] {
			return orderBy(
				this.pollsByCategory(this.currentCategory.id),
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

		/*
		* Sliced filtered and sorted polls for navigation
		*/
		navigationList: (state: PollList) => (filterId: FilterType) => {
			const useCategory = state.categories.find((category) => category.id === filterId)
			return orderBy(
				state.list.filter((poll) => useCategory.filterCondition(poll)),
				[SortType.Created],
				['desc'],
			).slice(0, state.meta.maxPollsInNavigation)
		},

		/*
		* Sliced filtered and sorted polls for dashboard
		*/
		dashboardList(state: PollList): Poll[] {
			const useCategory = state.categories.find((category) => category.id === FilterType.Relevant)
			return orderBy(
				state.list.filter((poll) => useCategory.filterCondition(poll)),
				[SortType.Created],
				['desc'],
			).slice(0, 7)
		},

		count(): number {
			return this.filteredRaw.length
		},

		loaded(state: PollList): number {
			return state.meta.loadedChunks * state.meta.chunksize
		},

		datePolls(state: PollList): Poll[] {
			return state.list.filter((poll) => (poll.type === PollType.Date && !poll.status.isDeleted))
		},

		pollsLoading(state): boolean {
			return state.meta.status === StatusResults.Loading
		},

		countByCategory: (state: PollList) => (filterId: string) =>
			state.list.filter((poll: Poll) =>
			state.categories.find((category: PollCategory) =>
				category.id === filterId
			).filterCondition(poll)
		).length,
	},

	actions: {
		async load(): Promise<void> {
			this.meta.status = StatusResults.Loading
			try {
				const response = await PollsAPI.getPolls()
				this.list = response.data.list
				this.meta.status = StatusResults.Loaded
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
				this.meta.status = StatusResults.Error
				Logger.error('Error loading polls', { error: e.response })
				throw e
			}
		},

		async setSort(payload: { sortBy: SortType }): Promise<void> {
			if (this.sort.by === payload.sortBy) {
				this.sort.reverse = !this.sort.reverse
			} else {
				this.sort.reverse = true
			}
			this.sort.by = payload.sortBy
		},

		addChunk(): void {
			this.meta.loadedChunks = this.meta.loadedChunks + 1
		},

		resetChunks(): void {
			this.meta.loadedChunks = 1
		},

		async clone(payload: { pollId: number }) {
			try {
				const response = await PollsAPI.clonePoll(payload.pollId)
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error cloning poll', { error, payload })
				throw error
			} finally {
				this.load()
			}
		},

		async delete(payload: { pollId: number }) {
			try {
				await PollsAPI.deletePoll(payload.pollId)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting poll', { error, payload })
				throw error
			} finally {
				this.load()
			}
		},

		async toggleArchive(payload: { pollId: number }) {
			try {
				await PollsAPI.toggleArchive(payload.pollId)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error archiving/restoring poll', { error, payload })
				throw error
			} finally {
				this.load()
			}
		},

	},
})
