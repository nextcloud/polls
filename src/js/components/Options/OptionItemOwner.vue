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
			currentUser: (state) => state.poll.acl.currentUser,
			permissions: (state) => state.poll.acl.permissions,
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
