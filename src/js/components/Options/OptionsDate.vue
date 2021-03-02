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
		<ConfigBox v-if="countOptions" :title="t('polls', 'Available Options')" icon-class="icon-calendar-000">
			<transition-group is="ul">
				<OptionItem v-for="(option) in options"
					:key="option.id"
					:option="option"
					:show-confirmed="true"
					display="textBox"
					tag="li">
					<template #actions>
						<Actions v-if="acl.allowEdit" class="action">
							<ActionButton icon="icon-delete" @click="removeOption(option)">
								{{ t('polls', 'Delete option') }}
							</ActionButton>
						</Actions>

						<Actions v-if="acl.allowEdit" class="action">
							<ActionButton v-if="!pollIsClosed" icon="icon-polls-clone" @click="cloneOptionModal(option)">
								{{ t('polls', 'Clone option') }}
							</ActionButton>
							<ActionButton v-if="pollIsClosed" :icon="option.confirmed ? 'icon-polls-confirmed' : 'icon-polls-unconfirmed'"
								@click="confirmOption(option)">
								{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
							</ActionButton>
						</Actions>
					</template>
				</OptionItem>
			</transition-group>
		</ConfigBox>

		<EmptyContent v-else icon="icon-calendar">
			{{ t('polls', 'No vote options') }}
			<template #desc>
				{{ t('polls', 'Add some!') }}
			</template>
		</EmptyContent>

		<Modal v-if="cloneModal" :can-close="false">
			<OptionCloneDate :option="optionToClone" class="modal__content" @close="closeModal()" />
		</Modal>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import OptionCloneDate from './OptionCloneDate'
import ConfigBox from '../Base/ConfigBox'
import OptionItem from './OptionItem'
import moment from '@nextcloud/moment'
import { Actions, ActionButton, Modal, EmptyContent } from '@nextcloud/vue'
import { confirmOption, removeOption } from '../../mixins/optionMixins'
import { dateUnits } from '../../mixins/dateMixins'

export default {
	name: 'OptionsDate',

	components: {
		Actions,
		ActionButton,
		ConfigBox,
		EmptyContent,
		OptionCloneDate,
		Modal,
		OptionItem,
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
			sequence: {
				baseOption: {},
				unit: { name: t('polls', 'Week'), value: 'week' },
				step: 1,
				amount: 1,
			},
		}
	},

	computed: {
		...mapState({
			options: state => state.options.list,
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			pollIsClosed: 'poll/closed',
			countOptions: 'options/count',
		}),

		dateBaseOptionString() {
			return moment.unix(this.sequence.baseOption.timestamp).format('LLLL')
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

<style lang="scss" scoped>
	.option-item {
		border-bottom: 1px solid var(--color-border);
		&:active,
		&:hover {
			transition: var(--background-dark) 0.3s ease;
			background-color: var(--color-background-dark);
		}
	}

</style>
