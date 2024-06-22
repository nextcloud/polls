<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="comments">
		<CommentAdd v-if="pollStore.permissions.comment" />
		<Comments v-if="!showEmptyContent" />
		<NcEmptyContent v-else v-bind="emptyContentProps">
			<template #icon>
				<CommentsIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import CommentAdd from '../Comments/CommentAdd.vue'
import Comments from '../Comments/Comments.vue'
import { NcEmptyContent } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import CommentsIcon from 'vue-material-design-icons/CommentProcessing.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'
import { useCommentsStore } from '../../stores/comments.ts'

export default {
	name: 'SideBarTabComments',
	components: {
		CommentAdd,
		Comments,
		NcEmptyContent,
		CommentsIcon,
	},

	data() {
		return {
			emptyContentProps: {
				name: t('polls', 'No comments'),
				description: t('polls', 'Be the first.'),
			}
		}
	},

	computed: {
		...mapStores(usePollStore, useCommentsStore),

		showEmptyContent() {
			return this.commentsStore.list.length === 0
		},

	},

}
</script>
