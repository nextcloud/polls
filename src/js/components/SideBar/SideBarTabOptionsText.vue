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

		<ConfigBox :title="t('polls', 'Available Options')" icon-class="icon-toggle-filelist">
			<draggable v-model="sortOptions">
				<transition-group>
					<OptionItem v-for="(option) in sortOptions"
						:key="option.id"
						:option="option"
						:draggable="true"
						type="textPoll">
						<template v-slot:actions>
							<Actions v-if="acl.allowEdit" class="action">
								<ActionButton icon="icon-delete" @click="removeOption(option)">
									{{ t('polls', 'Delete option') }}
								</ActionButton>
							</Actions>
							<Actions v-if="acl.allowEdit" class="action">
								<ActionButton v-if="expired" :icon="option.confirmed ? 'icon-polls-yes' : 'icon-checkmark'"
									@click="confirmOption(option)">
									{{ option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option') }}
								</ActionButton>
							</Actions>
						</template>
					</OptionItem>
				</transition-group>
			</draggable>
		</ConfigBox>
		<div v-if="!options.length" class="emptycontent">
			<div class="icon-toggle-filelist" />
			{{ t('polls', 'There are no vote options specified.') }}
		</div>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { Actions, ActionButton } from '@nextcloud/vue'
import ConfigBox from '../Base/ConfigBox'
import draggable from 'vuedraggable'
import OptionItem from '../Base/OptionItem'
import InputDiv from '../Base/InputDiv'

export default {
	name: 'SideBarTabOptionsText',

	components: {
		Actions,
		ActionButton,
		ConfigBox,
		draggable,
		InputDiv,
		OptionItem,
	},

	data() {
		return {
			newPollText: '',
		}
	},

	computed: {
		...mapState({
			options: state => state.options,
			acl: state => state.acl,
		}),

		...mapGetters(['sortedOptions', 'expired']),

		sortOptions: {
			get() {
				return this.sortedOptions
			},
			set(value) {
				// TODO: improve, this is not the elegant way
				this.$store.commit('reorderOptions', value)
				this.$store.dispatch('updateOptions')
			},
		},

	},

	methods: {
		addOption() {
			if (this.newPollText) {
				this.$store.dispatch('addOptionAsync', {
					pollOptionText: this.newPollText,
				})
					.then(() => {
						this.newPollText = ''
					})
			}
		},

		removeOption(option) {
			this.$store.dispatch('removeOptionAsync', {
				option: option,
			})
		},

		confirmOption(option) {
			this.$store.dispatch('updateOptionAsync', { option: { ...option, confirmed: !option.confirmed } })
		},
	},
}
</script>

<style lang="scss" scoped>
	.option-item {
		border-bottom: 1px solid var(--color-border);
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

	.emptycontent {
		margin-top: 20vh;
	}

</style>
