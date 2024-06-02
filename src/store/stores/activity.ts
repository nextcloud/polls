/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { ActivityAPI } from '../../Api/index.js'
import { User, AppSettings, AppPermissions } from '../../Interfaces/interfaces.ts'
import { Vote } from './votes.ts'

interface Activity {
	token: string,
	currentUser: User,
	appPermissions: AppPermissions
	appSettings: AppSettings
}

interface Activities {
	list: Activity[]
}

export const useAclStore = defineStore('acl', {
	state: (): Activities => ({
		list: [],
	}),

	actions: {
		async load() {
			try {
				const response = await ActivityAPI.getActivities(this.$router.route.params.id)
				this.$patch({ list: response.data.ocs.data })
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
