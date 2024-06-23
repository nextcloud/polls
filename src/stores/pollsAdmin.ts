/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { orderBy } from 'lodash'
import { PollsAPI } from '../Api/index.js'
import { Poll } from './poll.ts'
import { getCurrentUser } from '@nextcloud/auth'
import { SortType, sortColumnsMapping, StoreStatus } from './polls.ts'
import { Logger } from '../helpers/index.ts'
export interface PollsAdminList {
	list: Poll[]
	meta: {
		status: StoreStatus
	}
	sort: {
		by: SortType
		reverse: boolean
	}
}

export const usePollsAdminStore = defineStore('pollsAdmin', {
	state: (): PollsAdminList => ({
		list: [],
		meta: {
			status: StoreStatus.Loaded,
		},
		sort: {
			by: SortType.Created,
			reverse: true,
		},
	}),

	getters: {
		sorted(state: PollsAdminList): Poll[] {
			return orderBy(
				this.list,
				[state.sort.by],
				[state.sort.reverse ? 'desc' : 'asc'],
			)
		},
	},

	actions: {
		async load(): Promise<void> {
			this.meta.status = StoreStatus.Loading
			if (!getCurrentUser().isAdmin) {
				return
			}

			try {
				const response = await PollsAPI.getPollsForAdmin()
				this.list = response.data
				this.meta.status = StoreStatus.Loaded
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.meta.status = StoreStatus.Error
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
				this.load()
			}
		},

	},
})
