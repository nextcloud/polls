/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosError } from '@nextcloud/axios'
import type { Poll } from './poll.types'
import type { FilterType, PollCategory, PollsStore } from './polls.types'

import orderBy from 'lodash/orderBy'
import { defineStore } from 'pinia'
import { PollsAPI } from '../Api'
import { NotAllowed } from '../Exceptions/Exceptions'
import { Logger } from '../helpers/modules/logger'
import { activeRoute } from '../routerState'
import { usePollGroupsStore } from './pollGroups'
import { pollCategories, sortOption } from './polls.constants'
import { useSessionStore } from './session'

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
			by: sortOption.interaction,
			reverse: true,
		},
		status: {
			loadingGroups: false,
		},
	}),

	getters: {
		navigationCategories(): PollCategory[] {
			return Object.values(pollCategories).filter((category) =>
				category.showInNavigation(),)
		},

		currentCategory(): PollCategory {
			const route = activeRoute.value
			if (route.meta.listPage && route.params.type) {
				return pollCategories[route.params.type as FilterType]
			}
			return pollCategories.relevant
		},

		/*
		 * polls list, filtered by current category and sorted
		 */
		pollsFilteredSorted(state: PollsStore): Poll[] {
			const pollGroupsStore = usePollGroupsStore()

			// if we are in a group route, return the polls of the current group
			if (activeRoute.value.meta.groupPage) {
				return pollGroupsStore.pollsInCurrendPollGroup
			}

			return orderBy(
				state.polls.filter((poll: Poll) =>
					this.currentCategory?.filterCondition(poll),) ?? [],
				[state.sort.by.sortProperty],
				[state.sort.reverse ? 'desc' : 'asc'],
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
						pollCategories[filterId].filterCondition(poll),) ?? [],
					[sortOption.interaction.sortProperty],
					['desc'],
				).slice(0, state.meta.maxPollsInNavigation),

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

			for (const [key, category] of Object.entries(pollCategories)) {
				count[key as FilterType] = state.polls.filter((poll: Poll) =>
					category.filterCondition(poll),).length
			}

			return count
		},

		/*
		 * Sliced filtered and sorted polls for dashboard
		 */
		dashboardList(state: PollsStore): Poll[] {
			return orderBy(
				state.polls.filter((poll: Poll) =>
					pollCategories.relevant.filterCondition(poll),),
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
				pollCategories[filterId].filterCondition(poll),).length,
	},

	actions: {
		/**
		 * Load all polls and poll groups from the API.
		 * This will set the `polls` and `pollGroups` state properties.
		 *
		 * This will also set the `meta.status` to `Loading` while the request is in progress,
		 * and to `Loaded` or `Error` when the request is finished.
		 *
		 * @param forced - If false, loading polls will only be done, when the status is not `Loaded`.
		 * @throws {Error} If the request fails and is not canceled.
		 * @return
		 */
		async load(forced: boolean = true): Promise<void> {
			const sessionStore = useSessionStore()

			if (!sessionStore.userStatus.isLoggedin) {
				this.polls = []
				this.meta.status = ''
				throw new NotAllowed('Not allowed to load polls; not logged in')
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
		 *
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
				Logger.error('Error taking over poll', {
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
