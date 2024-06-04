/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, PollsAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.js'

interface Subscription {
	subscribed: boolean
}

export const useSubscriptionStore = defineStore('subscription', {
	state: (): Subscription => ({
		subscribed: false,
	}),

	actions: {
		async get() {
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.getSubscription(this.$router.route.params.token)
				} else if (this.$router.route.name === 'vote') {
					response = await PollsAPI.getSubscription(this.$router.route.params.id)
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
	
		async update() {
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.setSubscription(this.$router.route.params.token, !this.subscribed)
				} else if (this.$router.route.name === 'vote') {
					response = await PollsAPI.setSubscription(this.$router.route.params.id, !this.subscribed)
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
