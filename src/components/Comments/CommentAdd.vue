<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="comment-add">
		<UserItem :user="currentUser" hide-names />

		<InputDiv v-model="comment"
			class="comment-add__input"
			:placeholder="t('polls', 'New comment …')"
			submit
			@submit="writeComment()" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { showError } from '@nextcloud/dialogs'
import { InputDiv } from '../Base/index.js'
import { t } from '@nextcloud/l10n'
import UserItem from '../User/UserItem.vue'

export default {
	name: 'CommentAdd',

	components: {
		InputDiv,
		UserItem,
	},

	data() {
		return {
			comment: '',
		}
	},

	computed: {
		...mapState({
			currentUser: (state) => state.acl.currentUser,
		}),

	},

	methods: {
		t,
		async writeComment() {
			if (this.comment) {
				try {
					await this.$store.dispatch('comments/add', { message: this.comment })
					this.comment = ''
				} catch {
					showError(t('polls', 'Error while saving comment'))
				}
			}
		},
	},
}
</script>

<style lang="scss">
	.comment-add {
		margin-bottom: 24px;
		display: flex;
		.comment-add__input {
			margin-left: 8px;
			flex: 1;
			align-items: center;
		}
	}
</style>
