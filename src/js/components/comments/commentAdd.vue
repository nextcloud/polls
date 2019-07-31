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

<template lang="html">
	<div class="newCommentRow comment new-comment">
		<user-div :user-id="currentUser" />

		<form class="commentAdd" name="send_comment">
			<input v-model="comment" class="message" data-placeholder="New Comment ...">
			<input v-show="!loading" class="submitComment icon-confirm" @click="writeComment">
			<span v-show="loading" class="icon-loading-small" style="float:right;" />
		</form>
	</div>
</template>

<script>
	export default {
		name: 'AddComment',
		data() {
			return {
				comment: ''
			}
		},

		computed: {
			currentUser() {
				return this.$store.state.poll.currentUser
			},
		},

		methods: {
			writeComment() {
				this.$store
					.dispatch('writeCommentPromise', this.comment)
					.then(response => {
						OC.Notification.showTemporary(t('polls', 'Your comment was added'))
					})
					.catch(error => {
						this.writingVote = false
						/* eslint-disable-next-line no-console */
						console.log('Error while saving comment - Error: ', error.response)
						OC.Notification.showTemporary(t('polls', 'Error while saving comment', { type: 'error' }))
					})

			},
		},
	}
</script>

<style lang="scss" scoped>
	.comment {
		margin-bottom: 30px;
	}

	.commentAdd {
		display: flex;
	}

	.message {
		margin-left: 40px;
		flex: 1;
		&:empty:before {
			content: attr(data-placeholder);
			color: grey;
		}
	}
	.submitComment {
		align-self: last baseline;
		width: 30px;
		margin: 0;
		padding: 7px 9px;
		background-color: transparent;
		border: none;
		opacity: 0.3;
		cursor: pointer;
	}

	.icon-loading-small {
		float: left;
		margin-top: 10px;
	}
</style>
