<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import moment from '@nextcloud/moment'
import linkifyStr from 'linkify-string'
import { showError } from '@nextcloud/dialogs'
import { ActionDelete } from '../Actions/index.ts'
import { t } from '@nextcloud/l10n'
import UserItem from '../User/UserItem.vue'
import { useSessionStore } from '../../stores/session.ts'
import { usePollStore } from '../../stores/poll.ts'
import { useCommentsStore } from '../../stores/comments.ts'
import { Comment, CommentsGrouped } from '../../Types/index.ts'

const sessionStore = useSessionStore()
const pollStore = usePollStore()
const commentsStore = useCommentsStore()

const { comment } = defineProps<{ comment: CommentsGrouped }>()

const dateCommentedRelative = computed(() =>
	moment.unix(comment.timestamp).fromNow(),
)

const isCurrentUser = computed(
	() => sessionStore.currentUser?.id === comment.user.id,
)

const isConfidential = computed(() => comment.confidential > 0)
const confidentialRecipient = computed(() => {
	if (!isConfidential.value) {
		return ''
	}
	if (comment.recipient && comment.recipient.id !== sessionStore.currentUser.id) {
		return t('polls', 'Confidential with {userName}', {
			userName: comment.recipient.displayName,
		})
	}
	return t('polls', 'Confidential')
})
/**
 *
 * @param subComment
 */
function linkify(subComment: string) {
	return linkifyStr(subComment)
}

/**
 *
 * @param comment
 */
async function deleteComment(comment: Comment) {
	try {
		await commentsStore.delete({ comment })
	} catch {
		showError(t('polls', 'Error while deleting the comment'))
	}
}

/**
 *
 * @param comment
 */
async function restoreComment(comment: Comment) {
	try {
		await commentsStore.restore({ comment })
	} catch {
		showError(t('polls', 'Error while restoring the comment'))
	}
}
</script>

<template>
	<div :class="['comment-item', { 'current-user': isCurrentUser }]">
		<UserItem :user="comment.user" hide-names />
		<div class="comment-item__content">
			<span class="comment-item__user">{{ comment.user.displayName }}</span>
			<span class="comment-item__date">{{ dateCommentedRelative }}</span>
			<span v-if="isConfidential" class="comment-item__confidential">
				{{ confidentialRecipient }}
			</span>

			<div
				v-for="subComment in comment.comments"
				:key="subComment.id"
				:class="[
					'comment-item__sub-comment',
					{ deleted: subComment.deleted },
				]">
				<!-- eslint-disable vue/no-v-html -->
				<span v-html="linkify(subComment.comment)" />
				<!-- eslint-enable vue/no-v-html -->

				<ActionDelete
					v-if="
						comment.user.id === sessionStore.currentUser?.id
						|| pollStore.currentUserStatus.isOwner
					"
					:name="
						subComment.deleted
							? t('polls', 'Restore comment')
							: t('polls', 'Delete comment')
					"
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
	text-align: end;
	&::before {
		content: ' ~ ';
	}
}

.comment-item__confidential {
	opacity: 0.5;
	font-size: 0.8em;
	text-align: end;
	&::before {
		content: ' (';
	}
	&::after {
		content: ') ';
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
		&.current-user {
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
