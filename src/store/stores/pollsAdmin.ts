/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PollsAPI } from '../../Api/index.js'
import { Poll } from './poll.ts'
import { getCurrentUser } from '@nextcloud/auth'

export interface PollsAdminList {
	list: Poll[]
}

export const usePollsStore = defineStore('polls', {
	state: (): PollsAdminList => ({
		list: [],
	}),

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
