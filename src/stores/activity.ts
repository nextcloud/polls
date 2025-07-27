/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

import { ActivityAPI } from '../Api'

import { useSessionStore } from './session'

import type { AxiosError } from '@nextcloud/axios'
import type { Activity, ActivitiyStore } from './activity.types'

export const useActivityStore = defineStore('activity', {
	state: (): ActivitiyStore => ({
		activities: [],
	}),

	actions: {
		async load(): Promise<void> {
			const sessionStore = useSessionStore()
			try {
				const response = await ActivityAPI.getActivities(
					sessionStore.currentPollId,
				)
				this.activities = response.data.ocs.data
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.$reset()
			}
		},
	},

	getters: {
		getActivitiesForPoll(state): Activity[] {
			const sessionStore = useSessionStore()
			// TODO: Learn: Why is activity.object_id === sessionStore.currentPollId always false?
			return state.activities.filter(
				(activity: Activity) =>
					activity.object_type === 'poll'
					&& activity.object_id - sessionStore.currentPollId === 0,
			)
		},
	},
})
