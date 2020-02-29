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
		<h2>{{ t('polls','Comments') }} </h2>
		<CommentAdd v-if="acl.allowComment" />
		<transition-group v-if="countComments" name="fade" class="comments"
			tag="ul">
			<li v-for="(comment) in sortedList" :key="comment.id">
				<div class="comment-item">
					<user-div :user-id="comment.userId" :display-name="comment.displayName" />
					<Actions v-if="comment.userId === acl.userId">
						<ActionButton icon="icon-delete" @click="deleteComment(comment)">
							{{ t('polls', 'Delete comment') }}
						</ActionButton>
					</Actions>
					<div class="date">
						{{ moment.utc(comment.dt).fromNow() }}
					</div>
				</div>

				<div class="message wordwrap comment-content">
					{{ comment.comment }}
				</div>
			</li>
		</transition-group>

		<div v-else class="emptycontent">
			<div class="icon-comment" />
			<p> {{ t('polls', 'No comments yet. Be the first.') }}</p>
		</div>
	</div>
</template>

<script>
import CommentAdd from './CommentAdd'
import sortBy from 'lodash/sortBy'
import { Actions, ActionButton } from '@nextcloud/vue'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'Comments',
	components: {
		Actions,
		ActionButton,
		CommentAdd
	},
	data() {
		return {
			sort: 'timestamp',
			reverse: true
		}
	},

	computed: {
		...mapState({
			comments: state => state.comments,
			acl: state => state.acl
		}),

		...mapGetters([
			'countComments'
		]),

		sortedList() {
			if (this.reverse) {
				return sortBy(this.comments.list, this.sort).reverse()
			} else {
				return sortBy(this.comments.list, this.sort)
			}
		}

	},

	methods: {
		deleteComment(comment) {
			this.$store.dispatch({ type: 'deleteComment', comment: comment })
				.then(() => {
					OC.Notification.showTemporary(t('polls', 'Comment deleted'), { type: 'success' })
				}, (error) => {
					OC.Notification.showTemporary(t('polls', 'Error while deleting the comment'), { type: 'error' })
					console.error(error.response)
				})
		}
	}
}
</script>

<style scoped lang="scss">
.comments {
	margin: 8px 0;
	padding-right: 12px;
}

#emptycontent, .emptycontent {
	margin-top: 0;
}

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
