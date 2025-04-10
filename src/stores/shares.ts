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
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error loading shares', {
					error,
					pollId: sessionStore.currentPollId,
				})
				throw error
			}
		},

		async add(user: User): Promise<void> {
			const sessionStore = useSessionStore()

			try {
				await SharesAPI.addUserShare(sessionStore.currentPollId, user)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error writing share', {
					error,
					payload: user,
				})
				throw error
			} finally {
				this.load()
			}
		},

		async addPublicShare(): Promise<void> {
			const sessionStore = useSessionStore()
			try {
				await SharesAPI.addPublicShare(sessionStore.currentPollId)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error writing share', { error })
				throw error
			} finally {
				this.load()
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
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error(`Error switching type to ${setTo}`, {
					error,
					payload,
				})
				this.load()
				throw error
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
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error changing email register setting', {
					error,
					payload,
				})
				this.load()
				throw error
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
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error writing share label', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async inviteAll(payload: { pollId: number }) {
			const response = await SharesAPI.inviteAll(payload.pollId)
			this.load()
			return response
		},
		async sendInvitation(payload: { share: Share }) {
			const response = await SharesAPI.sendInvitation(payload.share.token)
			this.load()
			return response
		},

		async resolveGroup(payload: { share: Share }): Promise<void> {
			try {
				await SharesAPI.resolveShare(payload.share.token)
				this.load()
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error exploding group', {
					error,
					payload,
				})
				throw error
			}
		},

		async lock(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.lockShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error locking share', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async unlock(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.unlockShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error unlocking share', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},

		async delete(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.deleteShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting share', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},
		async restore(payload: { share: Share }): Promise<void> {
			try {
				const response = await SharesAPI.restoreShare(payload.share.token)
				this.update(response.data)
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error restoring share', {
					error,
					payload,
				})
				this.load()
				throw error
			}
		},
	},
})
