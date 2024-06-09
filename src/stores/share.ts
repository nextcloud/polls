/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { PublicAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.js'
import { usePollStore } from './poll.ts'
import { useCommentsStore } from './comments.ts'
import { useVotesStore } from './votes.ts'
import { useOptionsStore } from './options.ts'
import { useSubscriptionStore } from './subscription.ts'
import { User, UserType } from '../Interfaces/interfaces.ts'
import { useRouterStore } from './router.ts'

export enum InvitationTypes {
	Email = 'email',
	External = 'external',
	Contact = 'contact',
}

export enum DirectShareTypes {
	User = 'user',
	Group = 'group',
	Admin = 'admin',
	Public = 'public',
}

export enum ShareTypes {
	None = '',
	Email = 'email',
	External = 'external',
	Contact = 'contact',
	User = 'user',
	Group = 'group',
	Admin = 'admin',
	Public = 'public'
}

export type InvitationType = keyof typeof InvitationTypes
export type DirectShareType = keyof typeof DirectShareTypes
export type ShareType = ShareTypes

export interface Share {
	displayName: string
	id: string | null
	invitationSent: boolean
	locked: boolean
	pollId: number | null
	token: string
	type: ShareTypes
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
		type: ShareTypes.None,
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
			type: UserType.User,
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
		async load() {
			const routerStore = useRouterStore()
			if (routerStore.name !== 'publicVote') {
				this.$reset()
				return
			}
	
			try {
				const response = await PublicAPI.getShare(routerStore.params.token)
				this.$patch(response.data.share)
				return response.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error retrieving share', { error })
				throw error
			}
		},
	
		async updateEmailAddress(payload) {
			const routerStore = useRouterStore()
			if (routerStore.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.setEmailAddress(routerStore.params.token, payload.emailAddress)
				this.$patch(response.data.share)

				pollStore.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing email address', { error, payload })
				throw error
			}
		},
	
		async updateDisplayName(payload) {
			const routerStore = useRouterStore()
			if (routerStore.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.setDisplayName(routerStore.params.token, payload.displayName)
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
			const routerStore = useRouterStore()
			if (routerStore.name !== 'publicVote') {
				return
			}
	
			try {
				const response = await PublicAPI.deleteEmailAddress(routerStore.params.token)
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
			const routerStore = useRouterStore()
			if (routerStore.name !== 'publicVote') {
				return
			}
	
			try {
				return await PublicAPI.resendInvitation(routerStore.params.token)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error sending invitation', { error, payload })
				throw error
			}
		},
	},
})
