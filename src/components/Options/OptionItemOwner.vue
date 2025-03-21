<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, PropType } from 'vue'
import { ActionDelete } from '../Actions/index.js'
import UserItem from '../User/UserItem.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'
import { useSessionStore } from '../../stores/session.ts'
import { useOptionsStore, Option } from '../../stores/options.ts'

const pollStore = usePollStore()
const sessionStore = useSessionStore()
const optionsStore = useOptionsStore()

const props = defineProps({
	option: {
		type: Object as PropType<Option>,
		default: undefined,
	},
	avatarSize: {
		type: Number,
		default: 32,
	},
})

const showDelete = computed(
	() =>
		!pollStore.permissions.edit &&
		sessionStore.currentUser.id === props.option.owner?.id,
)
</script>

<template>
	<div class="option-item-owner">
		<ActionDelete
			v-if="showDelete"
			:name="
				option.deleted
					? t('polls', 'Restore option')
					: t('polls', 'Delete option')
			"
			:restore="!!option.deleted"
			:timeout="0"
			@restore="optionsStore.restore({ option: props.option })"
			@delete="optionsStore.delete({ option: props.option })" />

		<UserItem
			v-else-if="props.option.owner"
			:user="option.owner"
			:icon-size="avatarSize"
			hide-names
			hide-user-status
			:tooltip-message="
				t('polls', '{displayName}\'s proposal', {
					displayName: option.owner.displayName,
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
