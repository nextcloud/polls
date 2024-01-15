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
	<div :style="cssVar">
		<transition-group is="ul" v-if="countOptions">
			<OptionItem v-for="(option) in options"
				:key="option.id"
				:option="option"
				:show-confirmed="true"
				:poll-type="pollType"
				display="textBox"
				tag="li">
				<template #icon>
					<OptionItemOwner v-if="permissions.addOptions"
						:avatar-size="16"
						:option="option"
						class="owner" />
				</template>
				<template v-if="permissions.edit" #actions>
					<NcActions v-if="!closed" class="action">
						<NcActionButton v-if="!option.deleted" @click="deleteOption(option)">
							<template #icon>
								<DeleteIcon />
							</template>
							{{ t('polls', 'Delete option') }}
						</NcActionButton>
						<NcActionButton v-if="option.deleted" @click="restoreOption(option)">
							<template #icon>
								<RestoreIcon />
							</template>
							{{ t('polls', 'Restore option') }}
						</NcActionButton>
						<NcActionButton v-if="!closed" @click="cloneOptionModal(option)">
							<template #icon>
								<CloneDateIcon />
							</template>
							{{ t('polls', 'Clone option') }}
						</NcActionButton>

						<NcActionButton v-if="!option.deleted && !closed" type="tertiary" @click="confirmOption(option)">
							<template #icon>
								<UnconfirmIcon v-if="option.confirmed" />
								<ConfirmIcon v-else />
							</template>
							{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
						</NcActionButton>
					</NcActions>
				</template>
			</OptionItem>
		</transition-group>

		<NcEmptyContent v-else :name="t('polls', 'No vote options')">
			<template #icon>
				<DatePollIcon />
			</template>

			<template #action>
				{{ t('polls', 'Add some!') }}
			</template>
		</NcEmptyContent>

		<NcModal v-if="cloneModal" size="small" :can-close="false">
			<OptionCloneDate :option="optionToClone" class="modal__content" @close="closeModal()" />
		</NcModal>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { NcActions, NcActionButton, NcEmptyContent, NcModal } from '@nextcloud/vue'
import OptionCloneDate from './OptionCloneDate.vue'
import OptionItem from './OptionItem.vue'
import { confirmOption, deleteOption, restoreOption } from '../../mixins/optionMixins.js'
import { dateUnits } from '../../mixins/dateMixins.js'
import CloneDateIcon from 'vue-material-design-icons/CalendarMultiple.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'

export default {
	name: 'OptionsDate',

	components: {
		CloneDateIcon,
		DatePollIcon,
		DeleteIcon,
		RestoreIcon,
		ConfirmIcon,
		UnconfirmIcon,
		NcActions,
		NcActionButton,
		NcEmptyContent,
		NcModal,
		OptionCloneDate,
		OptionItem,
		OptionItemOwner: () => import('./OptionItemOwner.vue'),
	},

	mixins: [
		confirmOption,
		dateUnits,
		deleteOption,
		restoreOption,
	],

	data() {
		return {
			cloneModal: false,
			optionToClone: {},
			pollType: 'datePoll',
		}
	},

	computed: {
		...mapState({
			options: (state) => state.options.list,
			acl: (state) => state.poll.acl,
			permissions: (state) => state.poll.acl.permissions,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			countOptions: 'options/count',
		}),

		cssVar() {
			return {
				'--content-deleted': `" (${t('polls', 'deleted')})"`,
			}
		},
	},

	methods: {
		closeModal() {
			this.cloneModal = false
		},

		cloneOptionModal(option) {
			this.optionToClone = option
			this.cloneModal = true
		},

	},
}
</script>
