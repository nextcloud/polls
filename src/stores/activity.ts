/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { ActivityAPI } from '../Api/index.js'
import { User, AppSettings, AppPermissions } from '../Interfaces/interfaces.ts'
import { Vote } from './votes.ts'
import { useRouterStore } from './router.ts'

interface Activity {
	token: string,
	currentUser: User,
	appPermissions: AppPermissions
	appSettings: AppSettings
}

interface Activities {
	list: Activity[]
}

export const useActivityStore = defineStore('activity', {
	state: (): Activities => ({
		list: [],
	}),

	actions: {
		async load() {
			const routerStore = useRouterStore()
			try {
				const response = await ActivityAPI.getActivities(routerStore.params.id)
				this.list = response.data.ocs.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.$reset()
			}
		},
		
		deleteActivities(payload: { userId: string }) {
			this.list = this.list.filter((vote: Vote) => vote.user.userId !== payload.userId)
		},
		
	},
})
