<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'

import linkifyStr from 'linkify-string'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'

import ActionDelete from '../Actions/modules/ActionDelete.vue'
import UserItem from '../User/UserItem.vue'
import UserBubble from '../User/UserBubble.vue'

import { useSessionStore } from '../../stores/session'
import { usePollStore } from '../../stores/poll'
import { useCommentsStore } from '../../stores/comments'
import { usePreferencesStore } from '../../stores/preferences'

import type { Comment, CommentsGrouped } from '../../stores/comments.types'

const sessionStore = useSessionStore()
const pollStore = usePollStore()
const commentsStore = useCommentsStore()
const preferencesStore = usePreferencesStore()

const { comment } = defineProps<{ comment: CommentsGrouped }>()

const dateCommentedRelative = computed(() =>
	DateTime.fromSeconds(comment.timestamp).toRelative(),
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
		return t('polls', 'Confidential with {displayName}', {
			displayName: comment.recipient.displayName,
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
const deletable = computed(
	() =>
		comment.user.id === sessionStore.currentUser?.id
		|| pollStore.currentUserStatus.isOwner,
)
</script>

<template>
	<div :class="['comment-item', { 'current-user': isCurrentUser }, deletable]">
		<UserItem
			v-if="!preferencesStore.user.useCommentsAlternativeStyling"
			:user="comment.user"
			hide-names />

		<div class="comment-item__content">
			<span
				v-if="!preferencesStore.user.useCommentsAlternativeStyling"
				class="comment-item__user">
				{{ comment.user.displayName }}
			</span>

			<UserBubble v-else-if="!isCurrentUser" :user="comment.user" />

			<span class="comment-item__date">{{ dateCommentedRelative }}</span>

			<span v-if="isConfidential" class="comment-item__confidential">
				{{ confidentialRecipient }}
			</span>

			<div
				v-for="subComment in comment.comments"
				:key="subComment.id"
				:class="[
					'comment-item__sub-comment',
					{ deletable },
					{ deleted: subComment.deleted },
				]">
				<!-- eslint-disable vue/no-v-html -->
				<span v-html="linkify(subComment.comment)" />
				<!-- eslint-enable vue/no-v-html -->

				<ActionDelete
					v-if="deletable"
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

		&.deletable:hover {
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
		flex-direction: row;
		// margin-right: 44px;
		&.current-user {
			flex-direction: row-reverse;
			margin-inline: 88px 0;
		}
		&:not(.current-user) .comment-item__sub-comment {
			margin-inline-start: 1.5rem;
		}
	}

	.current-user {
		.user-item {
			display: none;
		}
		.comment-item__date {
			grid-row: 999;
		}
		.comment-item__content {
			display: grid;
			border: solid 1px var(--color-primary-element-light);
			border-radius: var(--border-radius-element);
			background-color: var(--color-primary-element-light);
			box-shadow: 2px 2px 6px var(--color-box-shadow);
			padding-inline-start: 8px;
			padding-bottom: 10px;
		}

		.comment-item__user {
			display: none;
		}

		.comment-item__sub-comment {
			margin-inline-end: 4px;

			&.deletable:hover {
				margin-inline-start: -4px;
				padding-inline-start: 4px;
				border-radius: var(--border-radius-element);
			}
		}
	}
}
</style>
