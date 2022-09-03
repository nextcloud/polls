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
		<ActionDelete v-if="!acl.allowEdit && acl.userId === option.owner.userId"
			:title="t('polls', 'Delete option')"
			@delete="removeOption(option)" />

		<NcAvatar v-else-if="option.owner.userId && option.owner.userId !== pollOwner"
			:user="option.owner.userId"
			:display-name="option.owner.displayName"
			:is-no-user="option.owner.isNoUser"
			disable-menu
			:size="avatarSize"
			:tooltip-message="t('polls', '{displayName}\'s proposal', { displayName: option.owner.displayName })" />
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { NcAvatar } from '@nextcloud/vue'
import { removeOption } from '../../mixins/optionMixins.js'
import ActionDelete from '../Actions/ActionDelete.vue'

export default {
	name: 'OptionItemOwner',

	components: {
		NcAvatar,
		ActionDelete,
	},

	mixins: [
		removeOption,
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
			acl: (state) => state.poll.acl,
		}),

		...mapGetters({
		}),
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
