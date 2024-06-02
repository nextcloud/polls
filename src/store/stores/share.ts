/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'
import { usePollStore } from './poll.ts'
import { useCommentsStore } from './comments.ts'
import { useVotesStore } from './votes.ts'
import { useOptionsStore } from './options.ts'
import { useSubscriptionStore } from './subscription.ts'
import { User } from '../../Interfaces/interfaces.ts'

export type ShareType = 'email' | 'external' | 'contact' | 'user' | 'group' | 'admin' | 'public'

export interface Share {
	displayName: string
	id: string | null
	invitationSent: boolean
	locked: boolean
	pollId: number | null
	token: string
	type: '' | ShareType
	emailAddress: string
	userId: string
	publicPollEmail: string
	user: User
	reminderSent: boolean
	label: string
	URL: string
	voted: boolean
	deleted: boolean
}

const pollStore = usePollStore()
const commentsStore = useCommentsStore()
const votesStore = useVotesStore()
const optionsStore = useOptionsStore()
const subscriptionStore = useSubscriptionStore()

export const useShareStore = defineStore('share', {
	state: (): Share => ({
		displayName: '',
		id: null,
		invitationSent: false,
		locked: false,
		pollId: null,
		token: '',
		type: '',
		emailAddress: '',
		userId: '',
		publicPollEmail: 'optional',
		user: {
			userId: '',
			displayName: '',
			emailAddress: '',
			subName: '',
			subtitle: '',
			isNoUser: true,
			desc: '',
			type: 'user',
			id: '',
			user: '',
			organisation: '', 
			languageCode: '',
			localeCode: '',
			timeZone: '',
			icon: 'icon-user',
			categories: []
		},
		reminderSent: false,
		label: '',
		URL: '',
		voted: false,
		deleted: false,
	}),

	actions: {
		async load() {
			if (this.$router.route.name !== 'publicVote') {
				this.$reset()
				return
			}
	
			try {
				const response = await PublicAPI.getShare(this.$router.route.params.token)
				this.$patch(response.data.share)
				return response.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.debug('Error retrieving share', { error })
				throw error
			}
		},
	
		async updateEmailAddress(payload) {
			if (this.$router.route.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.setEmailAddress(this.$router.route.params.token, payload.emailAddress)
				this.$patch(response.data.share)

				pollStore.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing email address', { error, payload })
				throw error
			}
		},
	
		async updateDisplayName(payload) {
			if (this.$router.route.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.setDisplayName(this.$router.route.params.token, payload.displayName)
				this.$patch(response.data.share)
				pollStore.load()
				commentsStore.load()
				votesStore.load()
				optionsStore.load()

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error changing name', { error, payload })
				throw error
			}
		},
	
		async deleteEmailAddress() {
			if (this.$router.route.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.deleteEmailAddress(this.$router.route.params.token)
				this.$patch(response.data.share)
				subscriptionStore.$state.subscribed = false
				subscriptionStore.update()
				pollStore.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing email address', { error })
				throw error
			}
		},
	
		async resendInvitation(payload) {
			if (this.$router.route.name !== 'publicVote') {
				return
			}
	
			try {
				return await PublicAPI.resendInvitation(this.$router.route.params.token)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error sending invitation', { error, payload })
				throw error
			}
		},
	},
})
