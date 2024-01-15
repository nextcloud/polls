<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
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
