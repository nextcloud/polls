/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI, PollsAPI } from '../Api/index.ts'
import { Logger } from '../helpers/index.ts'
import { useSessionStore } from './session.ts'
import { ref } from 'vue'

export type Subscription = {
	subscribed: boolean
}

export const useSubscriptionStore = defineStore('subscription', () => {
	const subscribed = ref(false)

	async function load() {
		const sessionStore = useSessionStore()
		try {
			let response = null
			if (sessionStore.route.name === 'publicVote') {
				response = await PublicAPI.getSubscription(
					sessionStore.route.params.token,
				)
			} else if (sessionStore.route.name === 'vote') {
				response = await PollsAPI.getSubscription(
					sessionStore.route.params.id,
				)
			} else {
				subscribed.value = false
				return
			}
			subscribed.value = response.data.subscribed
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			subscribed.value = false
			throw error
		}
	}
	async function write() {
		const sessionStore = useSessionStore()
		try {
			let response = null
			if (sessionStore.route.name === 'publicVote') {
				response = await PublicAPI.setSubscription(
					sessionStore.route.params.token,
					!subscribed.value,
				)
			} else if (sessionStore.route.name === 'vote') {
				response = await PollsAPI.setSubscription(
					sessionStore.route.params.id,
					!subscribed.value,
				)
			} else {
				subscribed.value = false
				return
			}
			subscribed.value = response.data.subscribed
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error on changing subscription', error)
			throw error
		}
	}
	return { subscribed, load, write }
})
