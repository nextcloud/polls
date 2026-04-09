/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ref } from 'vue'
import { defineStore } from 'pinia'

import { PublicAPI, PollsAPI } from '../Api'
import { activeRoute } from '../router'
import { Logger } from '../helpers/modules/logger'

import { useSessionStore } from './session'

import type { AxiosError } from '@nextcloud/axios'

export const useSubscriptionStore = defineStore('subscription', () => {
	const subscribed = ref(false)

	const $reset = () => {
		subscribed.value = false
	}

	async function load() {
		const sessionStore = useSessionStore()
		try {
			const response = await (() => {
				if (activeRoute.value.meta.publicVotePage) {
					return PublicAPI.getSubscription(sessionStore.publicToken)
				}
				if (activeRoute.value.meta.internalVotePage) {
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

	async function write() {
		const sessionStore = useSessionStore()
		try {
			const response = await (() => {
				if (activeRoute.value.meta.publicVotePage) {
					return PublicAPI.setSubscription(
						sessionStore.publicToken,
						!subscribed.value,
					)
				}
				if (activeRoute.value.meta.internalVotePage) {
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
		$reset,
		write,
	}
})
