<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import ActionDelete from '../Actions/modules/ActionDelete.vue'
import UserItem from '../User/UserItem.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll'
import { useSessionStore } from '../../stores/session'
import { useOptionsStore } from '../../stores/options'

import type { Option } from '../../stores/options.types'

interface Props {
	option: Option
	avatarSize?: number
}

const { option, avatarSize = 32 } = defineProps<Props>()

const pollStore = usePollStore()
const sessionStore = useSessionStore()
const optionsStore = useOptionsStore()

const showDelete = computed(
	() =>
		!pollStore.permissions.edit
		&& sessionStore.currentUser.id === option.owner?.id,
)
</script>

<template>
	<div class="option-item-owner blabla">
		<ActionDelete
			v-if="showDelete"
			:name="
				option.deleted
					? t('polls', 'Restore option')
					: t('polls', 'Delete option')
			"
			:restore="!!option.deleted"
			:timeout="0"
			@restore="optionsStore.restore({ option: option })"
			@delete="optionsStore.delete({ option: option })" />

		<UserItem
			v-else-if="option.owner"
			:user="option.owner"
			:icon-size="avatarSize"
			hide-names
			hide-status
			:tooltip-message="
				t('polls', '{displayName}\'s proposal', {
					displayName: option.owner?.displayName ?? '',
				})
			" />
	</div>
</template>

<style lang="scss">
.option-item-owner {
	display: flex;
	align-items: center;
	justify-content: center;
}
</style>
