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
	<div>
		<transition-group is="ul" v-if="countOptions">
			<OptionItem v-for="(option) in options"
				:key="option.id"
				:option="option"
				:show-confirmed="true"
				:poll-type="pollType"
				display="textBox"
				tag="li">
				<template #icon>
					<OptionItemOwner v-if="acl.allowAddOptions"
						:avatar-size="16"
						:option="option"
						class="owner" />
				</template>
				<template v-if="acl.allowEdit" #actions>
					<ActionDelete v-if="!closed"
						:title="t('polls', 'Delete option')"
						@delete="removeOption(option)" />

					<Actions v-if="!closed" class="action">
						<ActionButton v-if="!closed" @click="cloneOptionModal(option)">
							<template #icon>
								<CloneDateIcon />
							</template>
							{{ t('polls', 'Clone option') }}
						</ActionButton>
					</Actions>
					<VueButton v-if="closed"
						v-tooltip="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
						type="tertiary"
						@click="confirmOption(option)">
						<template #icon>
							<UnconfirmIcon v-if="option.confirmed" />
							<ConfirmIcon v-else />
						</template>
					</VueButton>
				</template>
			</OptionItem>
		</transition-group>

		<EmptyContent v-else>
			<template #icon>
				<DatePollIcon />
			</template>

			<template #desc>
				{{ t('polls', 'Add some!') }}
			</template>

			{{ t('polls', 'No vote options') }}
		</EmptyContent>

		<Modal v-if="cloneModal" size="small" :can-close="false">
			<OptionCloneDate :option="optionToClone" class="modal__content" @close="closeModal()" />
		</Modal>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { Actions, ActionButton, Button as VueButton, EmptyContent, Modal } from '@nextcloud/vue'
import ActionDelete from '../Actions/ActionDelete.vue'
import OptionCloneDate from './OptionCloneDate.vue'
import OptionItem from './OptionItem.vue'
import { confirmOption, removeOption } from '../../mixins/optionMixins.js'
import { dateUnits } from '../../mixins/dateMixins.js'
import CloneDateIcon from 'vue-material-design-icons/CalendarMultiple.vue'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

export default {
	name: 'OptionsDate',

	components: {
		CloneDateIcon,
		ConfirmIcon,
		UnconfirmIcon,
		Actions,
		ActionButton,
		ActionDelete,
		EmptyContent,
		Modal,
		OptionCloneDate,
		OptionItem,
		VueButton,
		DatePollIcon,
		OptionItemOwner: () => import('./OptionItemOwner.vue'),
	},

	mixins: [
		confirmOption,
		dateUnits,
		removeOption,
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
			isOwner: (state) => state.poll.acl.isOwner,
		}),

		...mapGetters({
			closed: 'poll/isClosed',
			countOptions: 'options/count',
		}),
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
