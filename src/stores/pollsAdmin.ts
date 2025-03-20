/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import orderBy from 'lodash/orderBy'
import { getCurrentUser } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'
import { Logger } from '../helpers/index.ts'
import { PollsAPI } from '../Api/index.js'
import { SortType, sortColumnsMapping, PollList, FilterType, usePollsStore } from './polls.ts'
import { Poll } from './poll.ts'
import { StatusResults } from '../Types/index.ts'

export const usePollsAdminStore = defineStore('pollsAdmin', {
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
				id: FilterType.Admin,
				title: t('polls', 'Relevant'),
				titleExt: t('polls', 'Relevant polls'),
				description: t('polls', 'Relevant polls which are relevant or for you, because you are a participant or the owner or you are invited to.'),
				pinned: false,
				createDependent: false,
				filterCondition: () => null,
			},
		],
	}),

	getters: {
		sorted(state: PollList): Poll[] {
			return orderBy(
				this.list,
				[state.sort.by],
				[state.sort.reverse ? 'desc' : 'asc'],
			)
		},
	},

	actions: {
		async load(loadUserPolls = false): Promise<void> {
			this.meta.status = StatusResults.Loading
			if (!getCurrentUser().isAdmin) {
				return
			}

			try {
				const response = await PollsAPI.getPollsForAdmin()
				this.list = response.data
				if(loadUserPolls) {
					const pollsStore = usePollsStore()
					await pollsStore.load()
				}
				this.meta.status = StatusResults.Loaded
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.meta.status = StatusResults.Error
				console.error('Error loading polls', { error })
				throw error
			}
		},

		async setSort(payload: { sortBy: SortType }): Promise<void> {
			if (this.sort.by === sortColumnsMapping[payload.sortBy]) {
				this.sort.reverse = !this.sort.reverse
			} else {
				this.sort.reverse = true
			}
			this.sort.by = payload.sortBy
		},


		async takeOver(payload: { pollId: number }): Promise<void> {
			if (!getCurrentUser().isAdmin) {
				return
			}
	
			try {
				await PollsAPI.takeOver(payload.pollId)
				this.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				throw error
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
				this.load(true)
			}
		},

	},
})
