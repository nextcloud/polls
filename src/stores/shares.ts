/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { SharesAPI } from '../Api/index.ts'
import { Logger } from '../helpers/index.ts'
import { useSessionStore } from './session.ts'
import { User } from '../Types/index.ts'
import { AxiosError } from '@nextcloud/axios'
import { SentResults } from '../Api/modules/shares.ts'

export enum ShareType {
	Email = 'email',
	External = 'external',
	Contact = 'contact',
	User = 'user',
	Group = 'group',
	Admin = 'admin',
	Public = 'public',
	Circle = 'circle',
	ContactGroup = 'contactGroup',
	None = '',
}

export enum PublicPollEmailConditions {
	Mandatory = 'mandatory',
	Optional = 'optional',
	Disabled = 'disabled',
}
export type Share = {
	displayName: string
	id: string
	invitationSent: boolean
	locked: boolean
	pollId: number | null
	groupId: number | null
	token: string
	type: ShareType
	emailAddress: string
	userId: string
	publicPollEmail: PublicPollEmailConditions
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
			const invitationTypes = [
				ShareType.Email,
				ShareType.External,
				ShareType.Contact,
			]

			// sharetype which are active without sending an invitation
			const directShareTypes = [
				ShareType.User,
				ShareType.Group,
				ShareType.Admin,
				ShareType.Public,
			]
			return state.list.filter(
				(share) =>
					!share.locked
					&& (directShareTypes.includes(share.type)
						|| (invitationTypes.includes(share.type)
							&& (share.type === ShareType.External
								|| share.invitationSent
								|| share.voted))),
			)
		},

		locked: (state) => state.list.filter((share) => !!share.locked),
		unsentInvitations: (state) =>
			state.list.filter(
				(share) =>
					(share.user.emailAddress
						|| share.type === ShareType.Group
						|| share.type === ShareType.ContactGroup
						|| share.type === ShareType.Circle)
					&& !share.invitationSent
					&& !share.locked
					&& !share.voted,
			),
		public: (state) =>
			state.list.filter((share) => share.type === ShareType.Public),
		hasShares: (state) => state.list.length > 0,
		hasLocked() {
			return this.locked.length > 0
		},
	},

	actions: {
		async load(): Promise<void> {
			const sessionStore = useSessionStore()
			try {
				const response = await SharesAPI.getShares(
					sessionStore.currentPollId,
				)
				this.list = response.data.shares
			} catch (error) {
				this.handleError(error, 'Error loading shares', {
					pollId: sessionStore.currentPollId,
				})
			}
		},

		async add(user: User): Promise<void> {
			const sessionStore = useSessionStore()

			try {
				const response = await SharesAPI.addUserShare(
					sessionStore.currentPollId,
					user,
				)
				this.list.push(response.data.share)
			} catch (error) {
				this.handleError(error, 'Error adding user share', {
					pollId: sessionStore.currentPollId,
					payload: user,
				})
			}
		},

		async addPublicShare(): Promise<void> {
			const sessionStore = useSessionStore()
			try {
				const response = await SharesAPI.addPublicShare(
					sessionStore.currentPollId,
				)
				this.list.push(response.data.share)
			} catch (error) {
				this.handleError(error, 'Error adding public share', {
					pollId: sessionStore.currentPollId,
				})
			}
		},

		update(payload: { share: Share }): void {
			const foundIndex = this.list.findIndex(
				(share: Share) => share.id === payload.share.id,
			)
			Object.assign(this.list[foundIndex], payload.share)
		},

		async switchAdmin(payload: { share: Share }): Promise<void> {
			const setTo =
				payload.share.type === ShareType.User
					? ShareType.Admin
					: ShareType.User

			try {
				const response = await SharesAPI.switchAdmin(
					payload.share.token,
					setTo,
				)
				this.update(response.data)
			} catch (error) {
				this.handleError(error, `Error switching type to ${setTo}`, payload)
			}
		},

		async setPublicPollEmail(payload: {
			share: Share
			value: PublicPollEmailConditions
		}): Promise<void> {
			try {
				const response = await SharesAPI.setEmailAddressConstraint(
					payload.share.token,
					payload.value,
				)
				this.update(response.data)
			} catch (error) {
				this.handleError(
					error,
					'Error changing email register setting',
					payload,
				)
			}
		},

		async writeLabel(payload: { token: string; label: string }): Promise<void> {
			try {
				const response = await SharesAPI.writeLabel(
					payload.token,
					payload.label,
				)
				this.update(response.data)
			} catch (error) {
				this.handleError(error, 'Error writing label', payload)
			}
		},

		async inviteAll(payload: { pollId: number }) {
			try {
				const response = await SharesAPI.inviteAll(payload.pollId)
				this.load()
				return response
			} catch (error) {
				this.handleError(error, 'Error inviting all users', payload)
			}
		},
		async sendInvitation(payload: { share: Share }): Promise<{
			share: Share
			sentResult: null | SentResults
		} | void> {
			try {
				const response = await SharesAPI.sendInvitation(payload.share.token)
				this.load()
				return response.data
			} catch (error) {
				this.handleError(error, 'Error sending share invitation', payload)
			}
		},

		async resolveGroup(payload: { share: Share }): Promise<void> {
			try {
				await SharesAPI.resolveShare(payload.share.token)
				this.load()
			} catch (error) {
				this.handleError(error, 'Error resolving group share', payload)
			}
		},

		async lock(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.lockShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				this.handleError(error, 'Error locking share', payload)
			}
		},

		async unlock(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.unlockShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				this.handleError(error, 'Error unlocking share', payload)
			}
		},

		async delete(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.deleteShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				this.handleError(error, 'Error deleting share', payload)
			}
		},
		async restore(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.restoreShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				this.handleError(error, 'Error restoring share', payload)
			}
		},
		handleError(error: unknown, message: string, payload?: unknown): void {
			if ((error as AxiosError)?.code === 'ERR_CANCELED') return

			Logger.error(message, {
				error,
				payload,
			})

			this.load()
			throw error
		},
	},
})
