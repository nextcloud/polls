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
		<OptionsTextAdd v-if="!pollIsClosed" />
		<draggable v-model="reOrderedOptions">
			<transition-group>
				<OptionItem v-for="(option) in reOrderedOptions"
					:key="option.id"
					:option="option"
					:draggable="true">
					<template #icon>
						<OptionItemOwner v-if="acl.allowAddOptions"
							:avatar-size="16"
							:option="option"
							class="owner" />
					</template>
					<template #actions>
						<Actions v-if="acl.allowEdit" class="action">
							<ActionButton icon="icon-delete" @click="removeOption(option)">
								{{ t('polls', 'Delete option') }}
							</ActionButton>
						</Actions>
						<Actions v-if="acl.allowEdit" class="action">
							<ActionButton v-if="pollIsClosed"
								:icon="option.confirmed ? 'icon-polls-yes' : 'icon-checkmark'"
								@click="confirmOption(option)">
								{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
							</ActionButton>
						</Actions>
					</template>
				</OptionItem>
			</transition-group>
		</draggable>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { Actions, ActionButton } from '@nextcloud/vue'
import draggable from 'vuedraggable'
import OptionItem from './OptionItem'
import OptionItemOwner from '../Options/OptionItemOwner'
import OptionsTextAdd from './OptionsTextAdd'
import { confirmOption, removeOption } from '../../mixins/optionMixins'

export default {
	name: 'OptionsText',

	components: {
		Actions,
		ActionButton,
		draggable,
		OptionItem,
		OptionItemOwner,
		OptionsTextAdd,
	},

	mixins: [
		confirmOption,
		removeOption,
	],

	data() {
		return {
			newPollText: '',
		}
	},

	computed: {
		...mapState({
			options: (state) => state.options.list,
			acl: (state) => state.poll.acl,
		}),

		...mapGetters({
			pollIsClosed: 'poll/closed',
			countOptions: 'options/count',
		}),

		reOrderedOptions: {
			get() {
				return this.options
			},
			set(value) {
				this.$store.dispatch('options/reorder', value)
			},
		},

	},

	methods: {
		async addOption() {
			if (this.newPollText) {
				await this.$store.dispatch('options/add', { pollOptionText: this.newPollText })
				this.newPollText = ''
			}
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

	.optionAdd {
		display: flex;
	}

	.newOption {
		margin-left: 40px;
		flex: 1;
		&:empty:before {
			color: grey;
		}
	}

	.submit-option {
		width: 30px;
		background-color: transparent;
		border: none;
		opacity: 0.3;
		cursor: pointer;
	}

</style>
