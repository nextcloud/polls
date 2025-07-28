/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

import { CommentsAPI, PublicAPI } from '../Api'
import { groupComments, Logger } from '../helpers'

import { useSessionStore } from './session'

import type { AxiosError } from '@nextcloud/axios'
import type { Comment, CommentsStore } from './comments.types'

export const useCommentsStore = defineStore('comments', {
	state: (): CommentsStore => ({
		comments: [],
	}),

	getters: {
		count: (state) => state.comments.length,
		groupedComments: (state) => groupComments(state.comments),
	},
	actions: {
		async load() {
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.getComments(
							sessionStore.route.params.token as string,
						)
					}
					if (sessionStore.route.name === 'vote') {
						return CommentsAPI.getComments(sessionStore.currentPollId)
					}

					return null
				})()

				if (!response) {
					this.$reset()
					return
				}

				this.comments = response.data.comments
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				this.$reset()
			}
		},

		async add(payload: { message: string; confidential: boolean }) {
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.addComment(
							sessionStore.publicToken,
							payload.message,
							payload.confidential,
						)
					}
					if (sessionStore.route.name === 'vote') {
						return CommentsAPI.addComment(
							sessionStore.currentPollId,
							payload.message,
							payload.confidential,
						)
					}
					return null
				})()

				if (!response) {
					this.$reset()
					return
				}

				this.load()
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error writing comment', {
					error,
					payload,
				})
				throw error
			}
		},

		setItem(payload: { comment: Comment }) {
			const index = this.comments.findIndex(
				(comment) => comment.id === payload.comment.id,
			)

			if (index < 0) {
				this.comments.push(payload.comment)
			} else {
				this.comments[index] = Object.assign(
					this.comments[index],
					payload.comment,
				)
			}
		},

		async delete(payload: { comment: Comment }) {
			const sessionStore = useSessionStore()

			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.deleteComment(
							sessionStore.publicToken,
							payload.comment.id,
						)
					}
					return CommentsAPI.deleteComment(payload.comment.id)
				})()

				this.setItem({ comment: response.data.comment })
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error deleting comment', {
					error,
					payload,
				})
				throw error
			}
		},

		async restore(payload: { comment: Comment }) {
			const sessionStore = useSessionStore()
			try {
				const response = await (() => {
					if (sessionStore.route.name === 'publicVote') {
						return PublicAPI.restoreComment(
							sessionStore.publicToken,
							payload.comment.id,
						)
					}
					return CommentsAPI.restoreComment(payload.comment.id)
				})()

				this.setItem({ comment: response.data.comment })
			} catch (error) {
				if ((error as AxiosError)?.code === 'ERR_CANCELED') {
					return
				}
				Logger.error('Error restoring comment', {
					error,
					payload,
				})
				throw error
			}
		},
	},
})
