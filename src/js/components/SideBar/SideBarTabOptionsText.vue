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
		<ConfigBox :title="t('polls', 'Add a new text option')" icon-class="icon-add">
			<InputDiv v-model="newPollText" :placeholder="t('polls', 'Enter option text')"
				@input="addOption()" />
		</ConfigBox>

		<ConfigBox v-if="!showEmptyContent" :title="t('polls', 'Available Options')" icon-class="icon-toggle-filelist">
			<draggable v-model="sortOptions">
				<transition-group>
					<OptionItem v-for="(option) in sortOptions"
						:key="option.id"
						:option="option"
						:draggable="true">
						<template #actions>
							<Actions v-if="acl.allowEdit" class="action">
								<ActionButton icon="icon-delete" @click="removeOption(option)">
									{{ t('polls', 'Delete option') }}
								</ActionButton>
							</Actions>
							<Actions v-if="acl.allowEdit" class="action">
								<ActionButton v-if="closed" :icon="option.confirmed ? 'icon-polls-yes' : 'icon-checkmark'"
									@click="confirmOption(option)">
									{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
								</ActionButton>
							</Actions>
						</template>
					</OptionItem>
				</transition-group>
			</draggable>
		</ConfigBox>

		<EmptyContent v-else icon="icon-toggle-filelist">
			{{ t('polls', 'No vote options') }}
			<template #desc>
				{{ t('polls', 'Add some!') }}
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { Actions, ActionButton, EmptyContent } from '@nextcloud/vue'
import ConfigBox from '../Base/ConfigBox'
import draggable from 'vuedraggable'
import OptionItem from '../Base/OptionItem'
import InputDiv from '../Base/InputDiv'
import { confirmOption, removeOption } from '../../mixins/optionMixins'

export default {
	name: 'SideBarTabOptionsText',

	components: {
		Actions,
		ActionButton,
		ConfigBox,
		draggable,
		EmptyContent,
		InputDiv,
		OptionItem,
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
			options: state => state.poll.options,
			acl: state => state.poll.acl,
		}),

		...mapGetters({
			sortedOptions: 'poll/options/sorted',
			closed: 'poll/closed',
		}),

		showEmptyContent() {
			return this.sortedOptions.length === 0
		},

		sortOptions: {
			get() {
				return this.sortedOptions
			},
			set(value) {
				this.$store.dispatch('poll/options/reorder', value)
			},
		},

	},

	methods: {
		addOption() {
			if (this.newPollText) {
				this.$store.dispatch('poll/options/add', {
					pollOptionText: this.newPollText,
				})
					.then(() => {
						this.newPollText = ''
					})
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
