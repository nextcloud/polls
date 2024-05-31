/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { SharesAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const defaultShares = () => ({
	list: [],
})

const namespaced = true
const state = defaultShares()

const mutations = {
	set(state, payload) {
		state.list = payload.shares
	},

	delete(state, payload) {
		state.list = state.list.filter((share) => share.id !== payload.share.id)
	},

	reset(state) {
		Object.assign(state, defaultShares())
	},

	add(state, payload) {
		state.list.push(payload)
	},

	update(state, payload) {
		const foundIndex = state.list.findIndex((share) => share.id === payload.share.id)
		Object.assign(state.list[foundIndex], payload.share)
	},

	setShareProperty(state, payload) {
		const foundIndex = state.list.findIndex((share) => share.id === payload.id)
		Object.assign(state.list[foundIndex], payload)
	},

}

const getters = {
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
	hasLocked: (state, getters) => getters.locked.length > 0,
}

const actions = {
	async list(context) {
		try {
			const response = await SharesAPI.getShares(context.rootState.route.params.id)
			context.commit('set', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error loading shares', { error }, { pollId: context.rootState.route.params.id })
			throw error
		}
	},

	async add(context, payload) {
		try {
			await SharesAPI.addShare(context.rootState.route.params.id, payload.user)
			context.dispatch('list')
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error writing share', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async switchAdmin(context, payload) {
		const setTo = payload.share.user.type === 'user' ? 'admin' : 'user'

		try {
			const response = await SharesAPI.switchAdmin(payload.share.token, setTo)
			context.commit('update', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error(`Error switching type to ${setTo}`, { error }, { payload })
			context.dispatch('list')
			throw error
		}
	},

	async setPublicPollEmail(context, payload) {
		try {
			const response = await SharesAPI.setEmailAddressConstraint(payload.share.token, payload.value)
			context.commit('update', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error changing email register setting', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async writeLabel(context, payload) {
		try {
			const response = await SharesAPI.writeLabel(payload.token, payload.label)
			context.commit('update', response.data)
			return response.data
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error writing share label', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async inviteAll(context, payload) {
		try {
			const response = await SharesAPI.inviteAll(payload.pollId)
			context.dispatch('list')
			return response
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error sending invitation', { error, payload })
			context.dispatch('list')
			throw error
		}

	},
	async sendInvitation(context, payload) {
		try {
			const response = await SharesAPI.sendInvitation(payload.share.token)
			context.dispatch('list')
			return response
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error sending invitation', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async resolveGroup(context, payload) {
		try {
			await SharesAPI.resolveShare(payload.share.token)
			context.dispatch('list')
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error exploding group', error.response.data, { error, payload })
			throw error
		}
	},

	async lock(context, payload) {
		try {
			const response = await SharesAPI.lockShare(payload.share.token)
			context.commit('update', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error locking share', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async unlock(context, payload) {
		try {
			const response = await SharesAPI.unlockShare(payload.share.token)
			context.commit('update', response.data)

		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error unlocking share', { error, payload })
			context.dispatch('list')
			throw error
		}
	},

	async delete(context, payload) {
		try {
			const response = await SharesAPI.deleteShare(payload.share.token)
			context.commit('update', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error deleting share', { error, payload })
			context.dispatch('list')
			throw error
		}
	},
	async restore(context, payload) {
		try {
			const response = await SharesAPI.restoreShare(payload.share.token)
			context.commit('update', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error restoring share', { error, payload })
			context.dispatch('list')
			throw error
		}
	},
}

export default { namespaced, state, mutations, actions, getters }
