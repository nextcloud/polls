/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { CommentsAPI, PublicAPI } from '../Api/index.ts'
import { User } from '../Types/index.ts'
import { groupComments, Logger } from '../helpers/index.ts'
import { useSessionStore } from './session.ts'

export type Comment = {
	comment: string
	deleted: number
	id: number
	parent: number
	pollId: number
	timestamp: number
	user: User
}

export type Comments = {
	list: Comment[]
}

export interface CommentsGrouped extends Comment {
	comments: Comment[]
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
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.getComments(
						sessionStore.route.params.token,
					)
				} else if (sessionStore.route.name === 'vote') {
					response = await CommentsAPI.getComments(
						sessionStore.route.params.id,
					)
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
				if (sessionStore.route.name === 'publicVote') {
					await PublicAPI.addComment(
						sessionStore.route.params.token,
						payload.message,
					)
				} else if (sessionStore.route.name === 'vote') {
					await CommentsAPI.addComment(
						sessionStore.route.params.id,
						payload.message,
					)
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
			const index = this.list.findIndex(
				(comment) => parseInt(comment.id) === payload.comment.id,
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
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.deleteComment(
						sessionStore.route.params.token,
						payload.comment.id,
					)
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
				if (sessionStore.route.name === 'publicVote') {
					response = await PublicAPI.restoreComment(
						sessionStore.route.params.token,
						payload.comment.id,
						{ comment: payload.comment },
					)
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
