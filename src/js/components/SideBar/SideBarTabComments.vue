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
	<div class="comments">
		<CommentAdd v-if="acl.allowComment" />
		<Comments v-if="!showEmptyContent" />
		<EmptyContent v-else icon="icon-comment">
			{{ t('polls', 'No comments') }}
			<template #desc>
				{{ t('polls', 'Be the first.') }}
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import CommentAdd from '../Comments/CommentAdd'
import Comments from '../Comments/Comments'
import { EmptyContent } from '@nextcloud/vue'
import { mapGetters, mapState } from 'vuex'

export default {
	name: 'SideBarTabComments',
	components: {
		CommentAdd,
		Comments,
		EmptyContent,
	},

	computed: {
		...mapState({
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			countComments: 'poll/comments/count',
		}),

		showEmptyContent() {
			return this.countComments === 0
		},

	},

}
</script>
