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
			@restore="restoreOption(option)"
			@delete="deleteOption(option)" />

		<UserItem v-else-if="showOwner"
			:user="option.owner"
			:icon-size="avatarSize"
			hide-names
			hide-status
			:tooltip-message="t('polls', '{displayName}\'s proposal', { displayName: option.owner.displayName })" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { deleteOption, restoreOption } from '../../mixins/optionMixins.js'
import { ActionDelete } from '../Actions/index.js'
import UserItem from '../User/UserItem.vue'

export default {
	name: 'OptionItemOwner',

	components: {
		UserItem,
		ActionDelete,
	},

	mixins: [
		deleteOption,
		restoreOption,
	],

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
		...mapState({
			pollOwner: (state) => state.poll.owner.userId,
			currentUser: (state) => state.acl.currentUser,
			permissions: (state) => state.poll.permissions,
		}),

		showDelete() {
			return !this.permissions.edit && this.currentUser.userId === this.option.owner.userId

		},
		showOwner() {
			return this.option.owner.type !== 'empty' && this.option.owner.userId !== this.pollOwner
		},
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
