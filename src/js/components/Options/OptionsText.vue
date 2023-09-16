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
		<OptionsTextAdd v-if="!closed" />
		<draggable v-if="countOptions"
			v-model="reOrderedOptions"
			v-bind="dragOptions"
			@start="drag = true"
			@end="drag = false">
			<transition-group type="transition" :name="!drag ? 'flip-list' : null">
				<OptionItem v-for="(option) in reOrderedOptions"
					:key="option.id"
					:option="option"
					:poll-type="pollType"
					:draggable="true">
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
						<NcButton v-if="closed"
							v-tooltip="confirmationCaption"
							:aria-label="confirmationCaption"
							type="tertiary"
							@click="confirmOption(option)">
							<template #icon>
								<UnconfirmIcon v-if="option.confirmed" />
								<ConfirmIcon v-else />
							</template>
						</NcButton>
					</template>
				</OptionItem>
			</transition-group>
		</draggable>

		<NcEmptyContent v-else :title="t('polls', 'No vote options')">
			<template #icon>
				<TextPollIcon />
			</template>

			<template #action>
				{{ t('polls', 'Add some!') }}
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { NcButton, NcEmptyContent } from '@nextcloud/vue'
import draggable from 'vuedraggable'
import { ActionDelete } from '../Actions/index.js'
import OptionItem from './OptionItem.vue'
import OptionItemOwner from '../Options/OptionItemOwner.vue'
import { confirmOption, removeOption } from '../../mixins/optionMixins.js'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

export default {
	name: 'OptionsText',

	components: {
		ConfirmIcon,
		UnconfirmIcon,
		ActionDelete,
		NcEmptyContent,
		draggable,
		OptionItem,
		OptionItemOwner,
		NcButton,
		TextPollIcon,
		OptionsTextAdd: () => import('./OptionsTextAdd.vue'),
	},

	mixins: [
		confirmOption,
		removeOption,
	],

	data() {
		return {
			pollType: 'textPoll',
			drag: false,
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

		confirmationCaption() {
			return this.option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')
		},

		dragOptions() {
			return {
				animation: 200,
				group: 'description',
				disabled: false,
				ghostClass: 'ghost',
			}
		},

		reOrderedOptions: {
			get() {
				return this.options
			},
			set(value) {
				this.$store.dispatch('options/reorder', value)
			},
		},

	},
}
</script>

<style lang="scss">
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

	.flip-list-move {
		transition: transform 0.5s;
	}

	.no-move {
		transition: transform 0s;
	}

	.ghost {
		opacity: 0.5;
		background: var(--color-primary-element-hover);
	}

	.submit-option {
		width: 30px;
		background-color: transparent;
		border: none;
		opacity: 0.3;
		cursor: pointer;
	}

</style>
