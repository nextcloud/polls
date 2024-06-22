<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<TransitionGroup is="ul"
		name="fade"
		:class="['comments' , { 'alternativestyle': preferencesStore.user.commentStyling }]"
		:style="cssVar">
		<CommentItem v-for="(comment) in commentsStore.groupedComments"
			:key="comment.id"
			:comment="comment"
			tag="li" />
	</TransitionGroup>
</template>

<script>

import CommentItem from './CommentItem.vue'
import { mapStores } from 'pinia'
import { t } from '@nextcloud/l10n'
import { usePreferencesStore } from '../../stores/preferences.ts'
import { useCommentsStore } from '../../stores/comments.ts'

export default {
	name: 'Comments',
	components: {
		CommentItem,
	},

	computed: {
		...mapStores(usePreferencesStore, useCommentsStore),
		cssVar() {
			return {
				'--content-deleted': `" (${t('polls', 'deleted')})"`,
			}
		},

	},
}
</script>
