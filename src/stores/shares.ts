/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { SharesAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.ts'
import { useSessionStore } from './session.ts'
import { User, UserType } from '../Types/index.ts'

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

export type Shares = {
	list: Share[]
}

export const useSharesStore = defineStore('shares', {
	state: (): Shares => ({
		list: [],
	}),

	getters: {
		active: (state) => {
			// share types, which will be active, after the user gets his invitation
			const invitationTypes = [UserType.Email, UserType.External, UserType.Contact]

			// sharetype which are active without sending an invitation
			const directShareTypes = [UserType.User, UserType.Group, UserType.Admin, UserType.Public]
			return state.list.filter((share) => (!share.locked
				&& (directShareTypes.includes(share.user.type)
					|| (invitationTypes.includes(share.user.type) && (share.user.type === UserType.External || share.invitationSent || share.voted))
				)
			))
		},
	
		locked: (state) => state.list.filter((share) => (!!share.locked)),
		unsentInvitations: (state) => state.list.filter((share) =>
			(share.user.emailAddress || share.user.type === UserType.Group || share.user.type === UserType.ContactGroup || share.user.type === UserType.Circle)
			&& !share.invitationSent && !share.locked && !share.voted),
		public: (state) => state.list.filter((share) => share.user.type === UserType.Public),
		hasShares: (state) => state.list.length > 0,
		hasLocked() {
			return this.locked.length > 0
		}
	},
	
	actions: {
		async load(): Promise<void>{
			const sessionStore = useSessionStore()
			try {
				const response = await SharesAPI.getShares(sessionStore.route.params.id)
				this.list = response.data.shares
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loading shares', { error, pollId: sessionStore.route.params.id })
				throw error
			}
		},
	
		async add(payload: { user: { type: string, userId: string, displayName: string, emailAddress: string }} ): Promise<void> {
			const sessionStore = useSessionStore()
			try {
				await SharesAPI.addShare(sessionStore.route.params.id, payload.user)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing share', { error, payload })
				throw error
			} finally {
				this.load()
			}
		},
	
		update(payload: { share: Share }): void {
			const foundIndex = this.list.findIndex((share: Share) => share.id === payload.share.id)
			Object.assign(this.list[foundIndex], payload.share)
		},
	
		async switchAdmin(payload: { share: Share }): Promise<void>{
			const setTo = payload.share.user.type === UserType.User ? UserType.Admin : UserType.User
	
			try {
				const response = await SharesAPI.switchAdmin(payload.share.token, setTo)
				this.update(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error(`Error switching type to ${setTo}`, { error, payload })
				this.load()
				throw error
			}
		},
	
		async setPublicPollEmail(payload: { share: Share; value: string }): Promise<void> {
			try {
				const response = await SharesAPI.setEmailAddressConstraint(payload.share.token, payload.value)
				this.update(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error changing email register setting', { error, payload })
				this.load()
				throw error
			}
		},
	
		async writeLabel(payload: { token: string; label: string }): Promise<void> {
			try {
				const response = await SharesAPI.writeLabel(payload.token, payload.label)
				this.update(response.data)
				return response.data
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing share label', { error, payload })
				this.load()
				throw error
			}
		},
	
		async inviteAll(payload: { pollId: number }) {
			try {
				const response = await SharesAPI.inviteAll(payload.pollId)
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error sending invitation', { error, payload })
				throw error
			} finally {
				this.load()
			}
	
		},
		async sendInvitation(payload: { share: Share }) {
			try {
				const response = await SharesAPI.sendInvitation(payload.share.token)
				return response
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error sending invitation', { error, payload })
				throw error
			} finally {
				this.load()
			}
		},
	
		async resolveGroup(payload: { share: Share }): Promise<void> {
			try {
				await SharesAPI.resolveShare(payload.share.token)
				this.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error exploding group', { error, payload })
				throw error
			}
		},
	
		async lock(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.lockShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error locking share', { error, payload })
				this.load()
				throw error
			}
		},
	
		async unlock(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.unlockShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error unlocking share', { error, payload })
				this.load()
				throw error
			}
		},
	
		async delete(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.deleteShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting share', { error, payload })
				this.load()
				throw error
			}
		},
		async restore(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.restoreShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error restoring share', { error, payload })
				this.load()
				throw error
			}
		},
	},
})
