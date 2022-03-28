<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
	<div class="comment-item">
		<UserItem v-bind="comment.user" hide-names />
		<div class="comment-item__content">
			<span class="comment-item__user">{{ comment.user.displayName }}</span>
			<span class="comment-item__date">{{ dateCommentedRelative }}</span>
			<div v-for="(subComment) in comment.subComments"
				:key="subComment.id"
				class="comment-item__subcomment">
				<span class="comment-item__comment">
					{{ subComment.comment }}
				</span>
				<ActionDelete v-if="comment.user.userId === acl.userId || acl.isOwner"
					:title="t('polls', 'Delete comment')"
					@delete="deleteComment(subComment)" />
			</div>
		</div>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import { showError } from '@nextcloud/dialogs'
import { mapState } from 'vuex'
import ActionDelete from '../Actions/ActionDelete'

export default {
	name: 'CommentItem',
	components: {
		ActionDelete,
	},

	props: {
		comment: {
			type: Object,
			default: null,
		},
	},

	computed: {
		...mapState({
			acl: (state) => state.poll.acl,
		}),
		dateCommentedRelative() {
			return moment.unix(this.comment.timestamp).fromNow()
		},
	},

	methods: {
		async deleteComment(comment) {
			try {
				await this.$store.dispatch({ type: 'comments/delete', comment })
			} catch {
				showError(t('polls', 'Error while deleting the comment'))
			}
		},
	},
}
</script>

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
		text-align: right;
		&::before {
			content: ' ~ '
		}
	}

	.comment-item__content {
		margin-left: 8px;
		flex: 1 1;
		padding-top: 2px;

		.material-design-icon {
			// display: none;
			visibility: hidden;
		}

		.comment-item__subcomment {
			display: flex;
			align-items: center;

			&:hover {
				background: var(--color-background-hover);
				.material-design-icon {
					visibility: visible;
					// display: flex;
				}
			}
		}

		.comment-item__comment {
			hyphens: auto;
			flex: 1;
		}
	}

</style>
