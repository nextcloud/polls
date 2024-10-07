<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, PropType } from 'vue'
	import moment from '@nextcloud/moment'
	import linkifyStr from 'linkify-string'
	import { showError } from '@nextcloud/dialogs'
	import { ActionDelete } from '../Actions/index.js'
	import { t } from '@nextcloud/l10n'
	import UserItem from '../User/UserItem.vue'
	import { useSessionStore } from '../../stores/session.ts'
	import { usePollStore } from '../../stores/poll.ts'
	import { useCommentsStore } from '../../stores/comments.ts'
	import { Comment, CommentsGrouped } from '../../Types/index.ts'


	const sessionStore = useSessionStore()
	const pollStore = usePollStore()
	const commentsStore = useCommentsStore()

	const props = defineProps(
		{
			comment: {
				type: Object as PropType<CommentsGrouped>,
				default: null,
			},
		},
	)

	const dateCommentedRelative = computed(() => moment.unix(props.comment.timestamp).fromNow())

	const isCurrentUser = computed(() => sessionStore.currentUser.id === props.comment.user.id)

	function linkify(subComment: string) {
		return linkifyStr(subComment)
	}

	async function deleteComment(comment: Comment) {
		try {
			await commentsStore.delete({ comment })
		} catch {
			showError(t('polls', 'Error while deleting the comment'))
		}
	}

	async function restoreComment(comment: Comment) {
		try {
			await commentsStore.restore({ comment })
		} catch {
			showError(t('polls', 'Error while restoring the comment'))
		}
	}

</script>

<template>
	<div :class="['comment-item', {'current-user': isCurrentUser}]">
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

				<ActionDelete v-if="(comment.user.id === sessionStore.currentUser.id || pollStore.currentUserStatus.isOwner)"
					:name="subComment.deleted ? t('polls', 'Restore comment') : t('polls', 'Delete comment')"
					:restore="!!subComment.deleted"
					:timeout="0"
					@restore="restoreComment(subComment)"
					@delete="deleteComment(subComment)" />
			</div>
		</div>
	</div>
</template>

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
		text-align: right;
		&::before {
			content: ' ~ '
		}
	}

	.comment-item__content {
		margin-left: 8px;
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
			&.current-user {
				flex-direction: row;
			}
		}

		.comment-item__content {
			border: solid 1px var(--color-primary-element-light);
			border-radius: var(--border-radius-large);
			background-color: var(--color-primary-element-light);
			box-shadow: 2px 2px 6px var(--color-box-shadow);
			padding-left: 8px;
			padding-bottom: 10px;

			.comment-item__sub-comment {
				margin-right: 4px;

				&:hover {
					background: var(--color-primary-element-hover);
					color: var(--color-primary-element-light-hover);
					margin-left: -4px;
					padding-left: 4px;
					border-radius: var(--border-radius-large);
				}
			}
		}
	}

</style>
