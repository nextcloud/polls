/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.ts'
import { usePollStore } from './poll.ts'
import { useCommentsStore } from './comments.ts'
import { useVotesStore } from './votes.ts'
import { useOptionsStore } from './options.ts'
import { useSubscriptionStore } from './subscription.ts'
import { User, UserType } from '../Types/index.ts'
import { useSessionStore } from './session.ts'

export type Share = {
	displayName: string
	id: string | null
	invitationSent: boolean
	locked: boolean
	pollId: number | null
	token: string
	type: UserType
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

export const useShareStore = defineStore('share', {
	state: (): Share => ({
		displayName: '',
		id: null,
		invitationSent: false,
		locked: false,
		pollId: null,
		token: '',
		type: UserType.None,
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
			type: UserType.None,
			id: '',
			user: '',
			organisation: '', 
			languageCode: '',
			localeCode: '',
			timeZone: '',
			categories: []
		},
		reminderSent: false,
		label: '',
		URL: '',
		voted: false,
		deleted: false,
	}),

	actions: {
		async load(): Promise<void> {
			const sessionStore = useSessionStore()
			if (sessionStore.route.name !== 'publicVote') {
				this.$reset()
				return
			}
	
			try {
				const response = await PublicAPI.getShare(sessionStore.route.params.token)
				this.$patch(response.data.share)
				return response.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error retrieving share', { error })
				throw error
			}
		},
	
		async updateEmailAddress(payload: { emailAddress: string }): Promise<void> {
			const pollStore = usePollStore()
			const sessionStore = useSessionStore()

			if (sessionStore.route.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.setEmailAddress(sessionStore.route.params.token, payload.emailAddress)
				this.$patch(response.data.share)
				pollStore.load()

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing email address', { error, payload })
				throw error
			}
		},
	
		async updateDisplayName(payload: { displayName: string }): Promise<void> {
			const pollStore = usePollStore()
			const commentsStore = useCommentsStore()
			const votesStore = useVotesStore()
			const optionsStore = useOptionsStore()
			const sessionStore = useSessionStore()

			if (sessionStore.route.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.setDisplayName(sessionStore.route.params.token, payload.displayName)
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
	
		async deleteEmailAddress(): Promise<void>{
			const pollStore = usePollStore()
			const subscriptionStore = useSubscriptionStore()
			const sessionStore = useSessionStore()

			if (sessionStore.route.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.deleteEmailAddress(sessionStore.route.params.token)
				this.$patch(response.data.share)
				subscriptionStore.$state.subscribed = false
				subscriptionStore.write()
				pollStore.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing email address', { error })
				throw error
			}
		},
	
		async resendInvitation() {
			const sessionStore = useSessionStore()
			if (sessionStore.route.name !== 'publicVote') {
				return
			}
	
			try {
				return await PublicAPI.resendInvitation(sessionStore.route.params.token)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error sending invitation', { error, token: sessionStore.route.params.token })
				throw error
			}
		},
	},
})
