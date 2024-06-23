/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { CommentsAPI, PublicAPI } from '../Api/index.js'
import { User } from '../Interfaces/interfaces.ts'
import { groupComments, Logger } from '../helpers/index.js'
import { useSessionStore } from './session.ts'

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
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.router.name === 'publicVote') {
					response = await PublicAPI.getComments(sessionStore.router.params.token)
				} else if (sessionStore.router.name === 'vote') {
					response = await CommentsAPI.getComments(sessionStore.router.params.id)
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
			const sessionStore = useSessionStore()
			try {
				if (sessionStore.router.name === 'publicVote') {
					await PublicAPI.addComment(sessionStore.router.params.token, payload.message)
				} else if (sessionStore.router.name === 'vote') {
					await CommentsAPI.addComment(sessionStore.router.params.id, payload.message)
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
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.router.name === 'publicVote') {
					response = await PublicAPI.deleteComment(sessionStore.router.params.token, payload.comment.id)
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
			const sessionStore = useSessionStore()
			try {
				let response = null
				if (sessionStore.router.name === 'publicVote') {
					response = await PublicAPI.restoreComment(sessionStore.router.params.token, payload.comment.id, { comment: payload.comment })
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
