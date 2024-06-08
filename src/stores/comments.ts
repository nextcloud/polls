/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { CommentsAPI, PublicAPI } from '../Api/index.js'
import { User } from '../Interfaces/interfaces.ts'
import { groupComments, Logger } from '../helpers/index.js'
import { useRouterStore } from './router.ts'

interface Comment {
	comment: string
	deleted: number
	id: number
	parent: number
	pollId: number
	timestamp: number
	user: User
}

interface Comments {
	list: Comment[]
}

export const useCommentsStore = defineStore('comments', {
	state: (): Comments => ({
		list: [],
	}),

	getters: {
		count: (state) => state.list.length,
		groupedComments: (state) => groupComments(state.list),
	},	
	actions: {
		async load() {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.getComments(routerStore.params.token)
				} else if (routerStore.name === 'vote') {
					response = await CommentsAPI.getComments(routerStore.params.id)
				} else {
					this.$reset()
					return
				}
				this.list = response.data.comments
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.$reset()
			}
		},
	
		async add(payload: { message: string }) {
			const routerStore = useRouterStore()
			try {
				if (routerStore.name === 'publicVote') {
					await PublicAPI.addComment(routerStore.params.token, payload.message)
				} else if (routerStore.name === 'vote') {
					await CommentsAPI.addComment(routerStore.params.id, payload.message)
				} else {
					this.$reset()
					return
				}
				this.load()
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error writing comment', { error, payload })
				throw error
			}
		},
		
		setItem(payload: { comment: Comment }) {
			const index = this.list.findIndex((comment) =>
				parseInt(comment.id) === payload.comment.id,
			)
	
			if (index < 0) {
				this.list.push(payload.comment)
			} else {
				this.list[index] = Object.assign(this.list[index], payload.comment)
			}
		},
	
		async delete(payload: { comment: Comment }) {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.deleteComment(routerStore.params.token, payload.comment.id)
				} else {
					response = await CommentsAPI.deleteComment(payload.comment.id)
				}

				this.setItem({ comment: response.data.comment })

			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error deleting comment', { error, payload })
				throw error
			}
		},
	
		async restore(payload) {
			const routerStore = useRouterStore()
			try {
				let response = null
				if (routerStore.name === 'publicVote') {
					response = await PublicAPI.restoreComment(routerStore.params.token, payload.comment.id, { comment: payload.comment })
				} else {
					response = await CommentsAPI.restoreComment(payload.comment.id)
				}

				this.setItem({ comment: response.data.comment })
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error restoring comment', { error, payload })
				throw error
			}
		},
	
	},
})
