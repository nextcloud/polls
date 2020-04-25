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
		<div class="config-box">
			<label class="title icon-add">
				{{ t('polls', 'Add a new text option') }}
			</label>

			<InputDiv v-model="newPollText" :placeholder="t('polls', 'Enter option text')"
				@input="addOption()" />
		</div>

		<div class="config-box poll-table">
			<label class="title icon-toggle-filelist">
				{{ t('polls', 'Available Options') }}
			</label>
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
						</template>
					</OptionItem>
				</transition-group>
			</draggable>
		</div>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { Actions, ActionButton } from '@nextcloud/vue'
import draggable from 'vuedraggable'
import OptionItem from '../Base/OptionItem'
import InputDiv from '../Base/InputDiv'

export default {
	name: 'SideBarTabOptionsText',

	components: {
		Actions,
		ActionButton,
		draggable,
		InputDiv,
		OptionItem
	},

	data() {
		return {
			newPollText: ''
		}
	},

	computed: {
		...mapState({
			options: state => state.options,
			acl: state => state.acl
		}),

		...mapGetters(['sortedOptions']),

		sortOptions: {
			get() {
				return this.sortedOptions
			},
			set(value) {
				this.writeOptions(value)
			}
		}

	},

	methods: {
		writeOptions(value) {
			this.$store.commit('reorderOptions', value)
			this.$store.dispatch('updateOptions')
		},

		addOption() {
			if (this.newPollText) {
				this.$store.dispatch('addOptionAsync', {
					pollOptionText: this.newPollText
				})
					.then(() => {
						this.newPollText = ''
					})
			}
		},

		removeOption(option) {
			this.$store.dispatch('removeOptionAsync', {
				option: option
			})
		}
	}

}
</script>

<style lang="scss" scoped>
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
