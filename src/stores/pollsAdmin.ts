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
import { sortType, sortColumnsMapping } from './polls.ts'

export interface PollsAdminList {
	list: Poll[]
	sort: {
		by: sortType
		reverse: boolean
	}
}

export const usePollsAdminStore = defineStore('pollsAdmin', {
	state: (): PollsAdminList => ({
		list: [],
		sort: {
			by: sortType.Created,
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
			if (!getCurrentUser().isAdmin) {
				return
			}

			try {
				const response = await PollsAPI.getPollsForAdmin()
				this.list = response.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				console.error('Error loading polls', { error })
				throw error
			}
		},

		async setSort(payload: { sortBy: sortType }): Promise<void> {
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
	},
})
