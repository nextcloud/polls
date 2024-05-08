<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<TransitionGroup is="ul"
		name="fade"
		:class="['comments' , {'alternativestyle': commentStyling}]"
		:style="cssVar">
		<CommentItem v-for="(comment) in groupedComments"
			:key="comment.id"
			:comment="comment"
			tag="li" />
	</TransitionGroup>
</template>

<script>

import CommentItem from './CommentItem.vue'
import { mapGetters, mapState } from 'vuex'

export default {
	name: 'Comments',
	components: {
		CommentItem,
	},

	computed: {
		...mapState({
			commentStyling: (state) => state.settings.user.useCommentsAlternativeStyling,
		}),

		...mapGetters({
			groupedComments: 'comments/groupedComments',
		}),

		cssVar() {
			return {
				'--content-deleted': `" (${t('polls', 'deleted')})"`,
			}
		},

	},
}
</script>
