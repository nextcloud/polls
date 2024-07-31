/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, PollsAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.ts'
import { useSessionStore } from './session.ts'

export type Subscription = {
	subscribed: boolean
}

export const useSubscriptionStore = defineStore('subscription', {
	state: (): Subscription => ({
		subscribed: false,
	}),

	actions: {
		async load() {
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.getSubscription(sessionStore.route.params.token)
				} else if (sessionStore.route.name === 'vote') {
					response = await PollsAPI.getSubscription(sessionStore.route.params.id)
				} else {
					this.$reset()
					return
				}
				this.subscribed = response.data.subscribed
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.subscribed = false
				throw error
			}
		},
	
		async write() {
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.setSubscription(sessionStore.route.params.token, !this.subscribed)
				} else if (sessionStore.route.name === 'vote') {
					response = await PollsAPI.setSubscription(sessionStore.route.params.id, !this.subscribed)
				} else {
					this.$reset()
					return
				}
				this.subscribed = response.data.subscribed
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error on changing subscription' , error)
				throw error
			}
		},
	},
})
