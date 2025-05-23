<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="['comment-item', {currentuser: isCurrentUser}]">
		<UserItem :user="comment.user" hide-names />
		<div class="comment-item__content">
			<span class="comment-item__user">{{ comment.user.displayName }}</span>
			<span class="comment-item__date">{{ dateCommentedRelative }}</span>
			<div v-for="(subComment) in comment.comments"
				:key="subComment.id"
				:class="['comment-item__sub-comment', { deleted: subComment.deleted }]">
				<!-- eslint-disable vue/no-v-html -->
				<span v-html="linkify(subComment.comment)" />
				<!-- eslint-enable vue/no-v-html -->

				<ActionDelete v-if="(comment.user.userId === currentUser.userId || isOwner)"
					:name="subComment.deleted ? t('polls', 'Restore comment') : t('polls', 'Delete comment')"
					:restore="!!subComment.deleted"
					:timeout="0"
					@restore="restoreComment(subComment)"
					@delete="deleteComment(subComment)" />
			</div>
		</div>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import linkifyStr from 'linkify-string'
import { showError } from '@nextcloud/dialogs'
import { mapState } from 'vuex'
import { ActionDelete } from '../Actions/index.js'

export default {
	name: 'CommentItem',
	components: {
		ActionDelete,
	},

	props: {
		comment: {
			type: Object,
			default: null,
		},
	},

	computed: {
		...mapState({
			currentUser: (state) => state.acl.currentUser,
			isOwner: (state) => state.poll.currentUserStatus.isOwner,
		}),

		dateCommentedRelative() {
			return moment.unix(this.comment.timestamp).fromNow()
		},

		isCurrentUser() {
			return this.currentUser.userId === this.comment.user.userId
		},
	},

	methods: {
		linkify(subComment) {
			return linkifyStr(subComment)
		},

		async deleteComment(comment) {
			try {
				await this.$store.dispatch({ type: 'comments/delete', comment })
			} catch {
				showError(t('polls', 'Error while deleting the comment'))
			}
		},

		async restoreComment(comment) {
			try {
				await this.$store.dispatch({ type: 'comments/restore', comment })
			} catch {
				showError(t('polls', 'Error while restoring the comment'))
			}
		},
	},
}
</script>

<style lang="scss">
	.comment-item {
		display: flex;
		align-items: start;
		margin-bottom: 24px;
	}

	.comment-item__user {
		font-weight: 600;
		font-size: 0.9em;
	}

	.comment-item__date {
		opacity: 0.5;
		font-size: 0.8em;
		text-align: end;
		&::before {
			content: ' ~ '
		}
	}

	.comment-item__content {
		margin-inline-start: 8px;
		flex: 1 1;
		padding-top: 2px;

		.material-design-icon {
			visibility: hidden;
		}

		.comment-item__sub-comment {
			display: flex;
			align-items: center;

			&:hover {
				background: var(--color-background-hover);
				.material-design-icon {
					visibility: visible;
				}
			}

			> span {
				hyphens: auto;
				flex: 1;
				a {
					text-decoration-line: underline;
				}
			}

			&.deleted {
				opacity: 0.6;

				> span::after {
					content: var(--content-deleted);
					font-weight: bold;
					color: var(--color-error-text);
				}
			}

		}
	}

	// experimental
	.alternativestyle {
		.comment-item {
		flex-direction: row-reverse;
			&.currentuser {
				flex-direction: row;
			}
		}

		.comment-item__content {
			border: solid 1px var(--color-primary-element-light);
			border-radius: var(--border-radius-large);
			background-color: var(--color-primary-element-light);
			box-shadow: 2px 2px 6px var(--color-box-shadow);
			padding-inline-start: 8px;
			padding-bottom: 10px;

			.comment-item__sub-comment {
				margin-inline-end: 4px;

				&:hover {
					background: var(--color-primary-element-hover);
					color: var(--color-primary-element-light-hover);
					margin-inline-start: -4px;
					padding-inline-start: 4px;
					border-radius: var(--border-radius-large);
				}
			}
		}
	}

</style>
