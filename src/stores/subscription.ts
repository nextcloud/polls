/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, PollsAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.js'
import { useRouterStore } from './router.ts'

interface Subscription {
	subscribed: boolean
}

export const useSubscriptionStore = defineStore('subscription', {
	state: (): Subscription => ({
		subscribed: false,
	}),

	actions: {
		async load() {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.getSubscription(routerStore.params.token)
				} else if (routerStore.name === 'vote') {
					response = await PollsAPI.getSubscription(routerStore.params.id)
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
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.setSubscription(routerStore.params.token, !this.subscribed)
				} else if (routerStore.name === 'vote') {
					response = await PollsAPI.setSubscription(routerStore.params.id, !this.subscribed)
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
