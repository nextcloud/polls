/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { ActivityAPI } from '../Api/index.js'
import { Vote } from './votes.ts'
import { useSessionStore } from './session.ts'

export type Activity = {
	activity_id: number
	app: string
	type: string
	user: string
	subject: string
	subject_rich: []
	message: string
	message_rich: []
	object_type: string
	object_id: number
	link: string
	icon: string
	datetime: string
}

export type Activities = {
	list: Activity[]
}

export const useActivityStore = defineStore('activity', {
	state: (): Activities => ({
		list: [],
	}),

	actions: {
		async load() {
			const sessionStore = useSessionStore()
			try {
				const response = await ActivityAPI.getActivities(
					sessionStore.route.params.id,
				)
				this.list = response.data.ocs.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.$reset()
			}
		},

		deleteActivities(payload: { userId: string }) {
			this.list = this.list.filter(
				(vote: Vote) => vote.user.id !== payload.userId,
			)
		},
	},
})
