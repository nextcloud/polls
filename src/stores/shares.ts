/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { SharesAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.js'
import { Share } from './share.ts'
import { useRouterStore } from './router.ts'

interface Shares {
	list: Share[]
}

export const useSharesStore = defineStore('shares', {
	state: (): Shares => ({
		list: [],
	}),

	getters: {
		active: (state) => {
			// share types, which will be active, after the user gets his invitation
			const invitationTypes = ['email', 'external', 'contact']
			// sharetype which are active without sending an invitation
			const directShareTypes = ['user', 'group', 'admin', 'public']
			return state.list.filter((share) => (!share.locked
				&& (directShareTypes.includes(share.user.type)
					|| (invitationTypes.includes(share.user.type) && (share.user.type === 'external' || share.invitationSent || share.voted))
				)
			))
		},
	
		locked: (state) => state.list.filter((share) => (!!share.locked)),
		unsentInvitations: (state) => state.list.filter((share) =>
			(share.user.emailAddress || share.user.type === 'group' || share.user.type === 'contactGroup' || share.user.type === 'circle')
			&& !share.invitationSent && !share.locked && !share.voted),
		public: (state) => state.list.filter((share) => ['public'].includes(share.user.type)),
		hasShares: (state) => state.list.length > 0,
		hasLocked() {
			return this.locked.length > 0
		}
	},
	
	actions: {
		async load() {
			const routerStore = useRouterStore()
			try {
				const response = await SharesAPI.getShares(routerStore.params.id)
				this.list = response.data.shares
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error loading shares', { error, pollId: routerStore.params.id })
				throw error
			}
		},
	
		async add(payload) {
			const routerStore = useRouterStore()
			try {
				await SharesAPI.addShare(routerStore.params.id, payload.user)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing share', { error, payload })
				throw error
			} finally {
				this.load()
			}
		},
	
		update(payload): void {
			const foundIndex = this.list.findIndex((share: Share) => share.id === payload.share.id)
			Object.assign(this.list[foundIndex], payload.share)
		},
	
		async switchAdmin(payload: { share: Share }): Promise<void>{
			const setTo = payload.share.user.type === 'user' ? 'admin' : 'user'
	
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
	
		async writeLabel(payload: { token: string; label: string }) {
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
	
		async resolveGroup(payload: { share: Share }) {
			try {
				await SharesAPI.resolveShare(payload.share.token)
				this.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error exploding group', { error, payload })
				throw error
			}
		},
	
		async lock(payload: { share: Share }) {
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
	
		async unlock(payload: { share: Share }) {
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
	
		async delete(payload: { share: Share }) {
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
		async restore(payload: { share: Share }) {
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
