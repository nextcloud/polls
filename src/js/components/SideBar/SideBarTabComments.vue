<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="comments">
		<CommentAdd v-if="permissions.comment" />
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
import { mapGetters, mapState } from 'vuex'
import CommentsIcon from 'vue-material-design-icons/CommentProcessing.vue'
import { t } from '@nextcloud/l10n'

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
		...mapState({
			permissions: (state) => state.poll.permissions,
		}),

		...mapGetters({
			countComments: 'comments/count',
		}),

		showEmptyContent() {
			return this.countComments === 0
		},

	},

}
</script>
