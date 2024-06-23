<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="option-item-owner">
		<ActionDelete v-if="showDelete"
			:name="option.deleted ? t('polls', 'Restore option') : t('polls', 'Delete option')"
			:restore="!!option.deleted"
			:timeout="0"
			@restore="optionsStore.restore(option)"
			@delete="optionsStore.delete(option)" />

		<UserItem v-else-if="showOwner"
			:user="option.owner"
			:icon-size="avatarSize"
			hide-names
			hide-user-status
			:tooltip-message="t('polls', '{displayName}\'s proposal', { displayName: option.owner.displayName })" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { ActionDelete } from '../Actions/index.js'
import UserItem from '../User/UserItem.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'
import { useSessionStore } from '../../stores/session.ts'
import { useOptionsStore } from '../../stores/options.ts'

export default {
	name: 'OptionItemOwner',

	components: {
		UserItem,
		ActionDelete,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		avatarSize: {
			type: Number,
			default: 32,
		},
	},

	computed: {
		...mapStores(usePollStore, useSessionStore, useOptionsStore),

		showDelete() {
			return !this.pollStore.permissions.edit && this.sessionStore.currentUser.userId === this.option.owner.userId

		},
		showOwner() {
			return this.option.owner.type !== 'empty' && this.option.owner.userId !== this.pollStore.owner.userId
		},
	},
	
	methods: {
		t,
	},
}

</script>

<style lang="scss">

.option-item-owner {
	display: flex;
	align-items: center;
	justify-content: center;
}

</style>
