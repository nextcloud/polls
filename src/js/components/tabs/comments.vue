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
	<div>
		<add-comment />
		<transition-group v-if="countComments" name="fade" class="comments"
			tag="ul"
		>
			<li v-for="(comment) in sortedComments" :key="comment.id">
				<div class="comment-item">
					<user-div :user-id="comment.userId" />
					<div class="date">
						{{ realtiveDate(comment.date) }}
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
import moment from 'moment'
import AddComment from '../comments/commentAdd'
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'CommentsTab',
	components: {
		AddComment
	},

	computed: {
		...mapState({
			comments: state => state.comments
		}),
		...mapGetters([
			'countComments',
			'sortedComments'
		])
	},

	mounted() {
		this.loadComments()
	},

	methods: {
		realtiveDate(date) {
			return t('core', moment.utc(date).fromNow())
		},

		loadComments() {
			this.$store.dispatch({ type: 'loadComments', pollId: this.$route.params.hash })
		}

	}
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
				margin-left: 44px;
				flex: 1 1;
			}
		}
	}
</style>
