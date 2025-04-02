<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :style="cssVar">
		<TransitionGroup is="ul"
			v-if="countOptions"
			name="list">
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
					<NcActions v-if="!isPollClosed" class="action">
						<NcActionButton v-if="!option.deleted" :name="t('polls', 'Delete option')" @click="deleteOption(option)">
							<template #icon>
								<DeleteIcon />
							</template>
						</NcActionButton>
						<NcActionButton v-if="option.deleted" :name="t('polls', 'Restore option')" @click="restoreOption(option)">
							<template #icon>
								<RestoreIcon />
							</template>
						</NcActionButton>
						<NcActionButton v-if="!isPollClosed" :name="t('polls', 'Clone option')" @click="cloneOptionModal(option)">
							<template #icon>
								<CloneDateIcon />
							</template>
						</NcActionButton>

						<NcActionButton v-if="!option.deleted && !isPollClosed"
							:name="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
							@click="confirmOption(option)">
							<template #icon>
								<UnconfirmIcon v-if="option.confirmed" />
								<ConfirmIcon v-else />
							</template>
						</NcActionButton>
					</NcActions>
				</template>
			</OptionItem>
		</TransitionGroup>

		<NcEmptyContent v-else
			:name="t('polls', 'No vote options')"
			:description="t('polls', 'Add some!')">
			<template #icon>
				<DatePollIcon />
			</template>
		</NcEmptyContent>

		<NcModal v-if="cloneModal" size="small" no-close>
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
import OptionItemOwner from './OptionItemOwner.vue'

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
		OptionItemOwner,
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
			permissions: (state) => state.poll.permissions,
		}),

		...mapGetters({
			isPollClosed: 'poll/isClosed',
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
