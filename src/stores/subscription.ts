/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ref } from 'vue'
import { defineStore } from 'pinia'

import { PublicAPI, PollsAPI } from '../Api'
import { Logger } from '../helpers'

import { useSessionStore } from './session'

import type { AxiosError } from '@nextcloud/axios'

export const useSubscriptionStore = defineStore('subscription', () => {
	const subscribed = ref(false)

	const reset = () => {
		subscribed.value = false
	}
	/**
	 *
	 */
	async function load() {
		const sessionStore = useSessionStore()
		try {
			const response = await (() => {
				if (sessionStore.route.name === 'publicVote') {
					return PublicAPI.getSubscription(sessionStore.route.params.token)
				}
				if (sessionStore.route.name === 'vote') {
					return PollsAPI.getSubscription(sessionStore.currentPollId)
				}

				return null
			})()

			if (response) {
				subscribed.value = response.data.subscribed
				return
			}

			subscribed.value = false
		} catch (error) {
			if ((error as AxiosError)?.code === 'ERR_CANCELED') {
				return
			}
			subscribed.value = false
			throw error
		}
	}
	/**
	 *
	 */
	async function write() {
		const sessionStore = useSessionStore()
		try {
			const response = await (() => {
				if (sessionStore.route.name === 'publicVote') {
					return PublicAPI.setSubscription(
						sessionStore.route.params.token,
						!subscribed.value,
					)
				}
				if (sessionStore.route.name === 'vote') {
					return PollsAPI.setSubscription(
						sessionStore.currentPollId,
						!subscribed.value,
					)
				}

				return null
			})()

			if (response) {
				subscribed.value = response.data.subscribed
				return
			}
			subscribed.value = false
		} catch (error: unknown) {
			if ((error as AxiosError)?.code === 'ERR_CANCELED') {
				return
			}
			Logger.error('Error on changing subscription', { error })
			throw error
		}
	}
	return {
		subscribed,
		load,
		reset,
		write,
	}
})
