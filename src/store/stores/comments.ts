/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { CommentsAPI, PublicAPI } from '../../Api/index.js'
import { User } from '../../Interfaces/interfaces.ts'
import { groupComments, Logger } from '../../helpers/index.js'

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
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.getComments(this.$router.route.params.token)
				} else if (this.$router.route.name === 'vote') {
					response = await CommentsAPI.getComments(this.$router.route.params.id)
				} else {
					this.$reset()
					return
				}
	
				this.$set(response.data.comments)
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				this.$reset()
			}
		},
	
		async add(payload: { message: string }) {
			try {
				if (this.$router.route.name === 'publicVote') {
					await PublicAPI.addComment(this.$router.route.params.token, payload.message)
				} else if (this.$router.route.name === 'vote') {
					await CommentsAPI.addComment(this.$router.route.params.id, payload.message)
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
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.deleteComment(this.$router.route.params.token, payload.comment.id)
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
			try {
				let response = null
				if (this.$router.route.name === 'publicVote') {
					response = await PublicAPI.restoreComment(this.$router.route.params.token, payload.comment.id, { comment: payload.comment })
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
