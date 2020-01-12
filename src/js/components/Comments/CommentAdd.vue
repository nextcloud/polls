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
	<div class="comment">
		<user-div :user-id="currentUser" />

		<form class="commentAdd" name="send-comment" @submit="writeComment">
			<input v-model="comment" class="message" data-placeholder="New Comment ...">
			<button v-show="!isLoading" type="submit" class="submit-comment icon-confirm" />
			<span v-show="isLoading" class="icon-loading-small" style="float:right;" />
		</form>
	</div>
</template>

<script>
export default {
	name: 'CommentAdd',
	data() {
		return {
			comment: '',
			isLoading: false
		}
	},

	computed: {
		currentUser() {
			return this.$store.state.acl.userId
		}
	},

	methods: {
		writeComment() {
			this.isLoading = true
			this.$store.dispatch('setCommentAsync', { message: this.comment })
				.then(() => {
					this.isLoading = false
					OC.Notification.showTemporary(t('polls', 'Your comment was added'), { type: 'success' })
					this.comment = ''
					this.isLoading = false
				})
				.catch((error) => {
					this.isLoading = false
					console.error('Error while saving comment - Error: ', error.response)
					OC.Notification.showTemporary(t('polls', 'Error while saving comment'), { type: 'error' })
				})

		}
	}
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

	.submit-comment {
		width: 30px;
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
