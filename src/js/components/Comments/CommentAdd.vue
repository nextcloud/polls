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
		<UserItem v-bind="acl" />
		<InputDiv v-model="comment" class="addComment" :placeholder="t('polls', 'New comment …')"
			@input="writeComment()" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import InputDiv from '../Base/InputDiv'

export default {
	name: 'CommentAdd',

	components: {
		InputDiv,
	},

	data() {
		return {
			comment: '',
			isLoading: false,
		}
	},

	computed: {
		...mapState({
			acl: state => state.acl,
		}),

	},

	methods: {
		writeComment() {
			if (this.comment) {
				this.isLoading = true
				this.$store.dispatch('setCommentAsync', { message: this.comment })
					.then(() => {
						this.isLoading = false
						OC.Notification.showTemporary(t('polls', 'Your comment was added'), { type: 'success' })
						this.comment = ''
					})
					.catch((error) => {
						this.isLoading = false
						console.error('Error while saving comment - Error: ', error.response)
						OC.Notification.showTemporary(t('polls', 'Error while saving comment'), { type: 'error' })
					})
			}

		},
	},
}
</script>

<style lang="scss" scoped>
	.comment {
		margin-bottom: 30px;
		.addComment {
			margin-left: 40px;
		}
	}

	.icon-loading-small {
		float: left;
		margin-top: 10px;
	}
</style>
