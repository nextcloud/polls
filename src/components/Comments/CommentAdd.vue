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
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { usePollStore } from '../../stores/poll.ts'

const commentsStore = useCommentsStore()
const sessionStore = useSessionStore()
const pollStore = usePollStore()
const comment = ref('')
const confidantial = ref(false)
const confidentialText =
	pollStore.owner.id === sessionStore.currentUser.id
		? t('polls', 'Only for me')
		: t('polls', 'Only for {displayName}', {
				displayName: pollStore.owner.displayName,
			})

/**
 *
 */
async function writeComment() {
	if (comment.value) {
		try {
			await commentsStore.add({
				message: comment.value,
				confidential: confidantial.value,
			})
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
		<div class="comment-add__input">
			<InputDiv
				v-model="comment"
				:placeholder="t('polls', 'New comment …')"
				submit
				@submit="writeComment()" />
			<NcCheckboxRadioSwitch v-model="confidantial" type="switch">
				{{ confidentialText }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<style lang="scss">
.comment-add {
	margin-bottom: 24px;
	display: flex;

	.user-item {
		align-items: first baseline;
	}
	.comment-add__input {
		margin-inline-start: 8px;
		flex: 1;
		align-items: center;
	}
}
</style>
