<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { showError } from '@nextcloud/dialogs'
import { InputDiv } from '../Base/index.ts'
import { t } from '@nextcloud/l10n'
import UserItem from '../User/UserItem.vue'
import { useSessionStore } from '../../stores/session.ts'
import { useCommentsStore } from '../../stores/comments.ts'

const commentsStore = useCommentsStore()
const sessionStore = useSessionStore()
const comment = ref('')

/**
 *
 */
async function writeComment() {
	if (comment.value) {
		try {
			await commentsStore.add({ message: comment.value })
			comment.value = ''
		} catch {
			showError(t('polls', 'Error while saving comment'))
		}
	}
}
</script>

<template>
	<div class="comment-add">
		<UserItem :user="sessionStore.currentUser" hide-names />

		<InputDiv
			v-model="comment"
			class="comment-add__input"
			:placeholder="t('polls', 'New comment …')"
			submit
			@submit="writeComment()" />
	</div>
</template>

<style lang="scss">
.comment-add {
	margin-bottom: 24px;
	display: flex;
	.comment-add__input {
		margin-inline-start: 8px;
		flex: 1;
		align-items: center;
	}
}
</style>
