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
			@submit="writeComment()" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import InputDiv from '../Base/InputDiv'

export default {
	name: 'CommentAdd',

	components: {
		InputDiv,
	},

	data() {
		return {
			comment: '',
		}
	},

	computed: {
		...mapState({
			acl: state => state.poll.acl,
		}),

	},

	methods: {
		writeComment() {
			if (this.comment) {
				this.$store.dispatch('poll/comments/add', { message: this.comment })
					.then(() => {
						this.comment = ''
					})
					.catch((error) => {
						console.error('Error while saving comment - Error: ', error.response)
						showError(t('polls', 'Error while saving comment'))
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
</style>
