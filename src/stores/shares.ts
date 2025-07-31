/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

import { Logger } from '../helpers'
import { SharesAPI } from '../Api'

import { usePollGroupsStore } from './pollGroups'
import { useSessionStore } from './session'

import type { AxiosError } from '@nextcloud/axios'
import type { SentResults } from '../Api/modules/shares'
import type { User } from '../Types'
import type {
	Share,
	SharesStore,
	SharePurpose,
	PublicPollEmailConditions,
} from './shares.types'

export const useSharesStore = defineStore('shares', {
	state: (): SharesStore => ({
		shares: [],
	}),

	getters: {
		active: (state) => {
			// share types, which will be active, after the user gets his invitation
			const invitationTypes = ['email', 'external', 'contact']

			// sharetype which are active without sending an invitation
			const directShareTypes = ['user', 'group', 'admin', 'public']
			return state.shares.filter(
				(share) =>
					!share.locked
					&& (directShareTypes.includes(share.type)
						|| (invitationTypes.includes(share.type)
							&& (share.type === 'external'
								|| share.invitationSent
								|| share.voted))),
			)
		},

		locked: (state) => state.shares.filter((share) => !!share.locked),
		unsentInvitations: (state) =>
			state.shares.filter(
				(share) =>
					(share.user.emailAddress
						|| share.type === 'group'
						|| share.type === 'contactGroup'
						|| share.type === 'circle')
					&& !share.invitationSent
					&& !share.locked
					&& !share.voted,
			),
		public: (state) => state.shares.filter((share) => share.type === 'public'),
		hasShares: (state) => state.shares.length > 0,
		hasLocked() {
			return this.locked.length > 0
		},
	},

	actions: {
		async load(purpose: SharePurpose = 'poll'): Promise<void> {
			let pollOrPollGroupId: number = 0

			if (purpose === 'pollGroup') {
				const pollGroupsStore = usePollGroupsStore()
				Logger.info('Loading group shares')
				// For group shares, we need to use the current poll group ID

				if (!pollGroupsStore.currentPollGroup) {
					throw new Error('Current group is not set')
				}
				pollOrPollGroupId = pollGroupsStore.currentPollGroup.id
			} else {
				Logger.info('Loading poll shares')
				// For regular poll shares, we use the current poll ID
				const sessionStore = useSessionStore()
				pollOrPollGroupId = sessionStore.currentPollId
			}

			try {
				const response = await SharesAPI.getShares(
					pollOrPollGroupId,
					purpose,
				)
				this.shares = response.data.shares
			} catch (error) {
				this.handleError(error, 'Error loading shares', {
					pollId: pollOrPollGroupId,
				})
			}
		},

		async add(user: User, purpose: SharePurpose = 'poll'): Promise<void> {
			let pollOrPollGroupId: number = 0

			if (purpose === 'pollGroup') {
				const pollGroupsStore = usePollGroupsStore()
				// For group shares, we need to use the current poll group ID

				if (!pollGroupsStore.currentPollGroup) {
					throw new Error('Current group is not set')
				}

				pollOrPollGroupId = pollGroupsStore.currentPollGroup.id
			} else {
				// For regular poll shares, we use the current poll ID
				const sessionStore = useSessionStore()
				pollOrPollGroupId = sessionStore.currentPollId
			}

			try {
				const response = await SharesAPI.addUserShare(
					pollOrPollGroupId,
					user,
					purpose,
				)
				this.shares.push(response.data.share)
			} catch (error) {
				this.handleError(error, 'Error adding user share', {
					purpose,
					id: pollOrPollGroupId,
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
				this.shares.push(response.data.share)
			} catch (error) {
				this.handleError(error, 'Error adding public share', {
					pollId: sessionStore.currentPollId,
				})
			}
		},

		update(payload: { share: Share }): void {
			const foundIndex = this.shares.findIndex(
				(share: Share) => share.id === payload.share.id,
			)
			Object.assign(this.shares[foundIndex], payload.share)
		},

		async switchAdmin(payload: { share: Share }): Promise<void> {
			const setTo = payload.share.type === 'user' ? 'admin' : 'user'

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
