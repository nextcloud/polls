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
	<transition-group name="fade" class="comments"
		tag="ul">
		<li v-for="(comment) in sortedList" :key="comment.id">
			<div class="comment-item">
				<UserItem v-bind="comment" />
				<Actions v-if="comment.userId === acl.userId || acl.isOwner">
					<ActionButton icon="icon-delete" @click="deleteComment(comment)">
						{{ t('polls', 'Delete comment') }}
					</ActionButton>
				</Actions>
				<div class="date">
					{{ dateCommentedRelative(comment.dt) }}
				</div>
			</div>

			<div class="message wordwrap comment-content">
				{{ comment.comment }}
			</div>
		</li>
	</transition-group>
</template>

<script>
import sortBy from 'lodash/sortBy'
import moment from '@nextcloud/moment'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { Actions, ActionButton } from '@nextcloud/vue'
import { mapState } from 'vuex'

export default {
	name: 'Comments',
	components: {
		Actions,
		ActionButton,
	},
	data() {
		return {
			sort: 'timestamp',
			reverse: true,
		}
	},

	computed: {
		...mapState({
			comments: state => state.poll.comments.list,
			acl: state => state.poll.acl,
		}),

		sortedList() {
			if (this.reverse) {
				return sortBy(this.comments, this.sort).reverse()
			} else {
				return sortBy(this.comments, this.sort)
			}
		},

	},

	methods: {
		deleteComment(comment) {
			this.$store
				.dispatch({ type: 'poll/comments/delete', comment: comment })
				.then(() => {
					showSuccess(t('polls', 'Comment deleted'))
				})
				.catch((error) => {
					showError(t('polls', 'Error while deleting the comment'))
					console.error(error.response)
				})
		},
		dateCommentedRelative(date) {
			return moment.utc(date).fromNow()
		},
	},
}
</script>

<style scoped lang="scss">
	ul {
		& > li {
			margin-bottom: 30px;
			& > .comment-item {
				display: flex;
				align-items: center;

				& > .date {
					right: 0;
					top: 5px;
					opacity: 0.5;
				}
			}
			& > .message {
				margin-left: 53px;
				flex: 1 1;
			}
		}
	}
</style>
