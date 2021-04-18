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
		<div class="comment-item_meta">
			<UserItem v-bind="comment" />
			<div class="date">
				{{ dateCommentedRelative }}
			</div>
			<Actions v-if="comment.userId === acl.userId || acl.isOwner">
				<ActionButton v-if="deleteTimeout" icon="icon-history" @click="cancelDeleteComment()">
					{{ n('polls', 'Deleting in {countdown} second', 'Deleting in {countdown} seconds', countdown, { countdown }) }}
				</ActionButton>
				<ActionButton v-else icon="icon-delete" @click="deleteComment()">
					{{ t('polls', 'Delete comment') }}
				</ActionButton>
			</Actions>
		</div>

		<div class="comment-item_content">
			{{ comment.comment }}
		</div>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import { showError } from '@nextcloud/dialogs'
import { Actions, ActionButton } from '@nextcloud/vue'
import { mapState } from 'vuex'

export default {
	name: 'CommentItem',
	components: {
		Actions,
		ActionButton,
	},

	props: {
		comment: {
			type: Object,
			default: null,
		},
	},

	data() {
		return {
			deleteInterval: null,
			deleteTimeout: null,
			countdown: 7,
		}
	},

	computed: {
		...mapState({
			acl: (state) => state.poll.acl,
		}),
		dateCommentedRelative() {
			return moment.utc(this.comment.dt).fromNow()
		},
	},

	methods: {
		deleteComment() {
			this.deleteInterval = setInterval(() => {
				this.countdown -= 1
				if (this.countdown < 0) {
					this.countdown = 0
				}
			}, 1000)
			this.deleteTimeout = setTimeout(async() => {
				try {
					await this.$store.dispatch({ type: 'comments/delete', comment: this.comment })
				} catch {
					showError(t('polls', 'Error while deleting the comment'))
				} finally {
					clearInterval(this.deleteInterval)
					this.deleteTimeout = null
					this.deleteInterval = null
					this.countdown = 7
				}
			}, 7000)
		},

		cancelDeleteComment() {
			clearTimeout(this.deleteTimeout)
			clearInterval(this.deleteInterval)
			this.deleteTimeout = null
			this.deleteInterval = null
			this.countdown = 7
		},
	},
}
</script>

<style scoped lang="scss">
	.comment-item {
		margin-bottom: 30px;
	}

	.comment-item_meta {
		display: flex;
		align-items: center;
	}

	.date {
		right: 0;
		top: 5px;
		opacity: 0.5;
	}

	.comment-item_content {
		margin-left: 53px;
		flex: 1 1;
	}
</style>
