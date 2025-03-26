<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :style="cssVar">
		<OptionsTextAdd v-if="!isPollClosed" />
		<draggable v-if="countOptions"
			v-model="reOrderedOptions"
			v-bind="dragOptions"
			@start="drag = true"
			@end="drag = false">
			<TransitionGroup type="transition" :name="!drag ? 'flip-list' : 'list'">
				<OptionItem v-for="(option) in reOrderedOptions"
					:key="option.id"
					:option="option"
					:poll-type="pollType"
					:draggable="true">
					<template #icon>
						<OptionItemOwner v-if="permissions.addOptions"
							:avatar-size="16"
							:option="option"
							class="owner" />
					</template>
					<template v-if="permissions.edit" #actions>
						<NcActions v-if="!isPollClosed" class="action">
							<NcActionButton v-if="!option.deleted"
								:name="t('polls', 'Delete option')"
								:aria-label="t('polls', 'Delete option')"
								@click="deleteOption(option)">
								<template #icon>
									<DeleteIcon />
								</template>
							</NcActionButton>
							<NcActionButton v-if="option.deleted"
								:name="t('polls', 'Restore option')"
								:aria-label="t('polls', 'Restore option')"
								@click="restoreOption(option)">
								<template #icon>
									<RestoreIcon />
								</template>
							</NcActionButton>
							<NcActionButton v-if="!option.deleted && !isPollClosed"
								:name="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
								:aria-label="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
								type="tertiary"
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
		</draggable>

		<NcEmptyContent v-else
			:name="t('polls', 'No vote options')"
			:description="t('polls', 'Add some!')">
			<template #icon>
				<TextPollIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { NcActions, NcActionButton, NcEmptyContent } from '@nextcloud/vue'
import draggable from 'vuedraggable'
import OptionItem from './OptionItem.vue'
import OptionItemOwner from '../Options/OptionItemOwner.vue'
import { confirmOption, deleteOption, restoreOption } from '../../mixins/optionMixins.js'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import OptionsTextAdd from './OptionsTextAdd.vue'

export default {
	name: 'OptionsText',

	components: {
		draggable,
		DeleteIcon,
		RestoreIcon,
		TextPollIcon,
		ConfirmIcon,
		UnconfirmIcon,
		NcEmptyContent,
		OptionItem,
		OptionItemOwner,
		NcActions,
		NcActionButton,
		OptionsTextAdd,
	},

	mixins: [
		confirmOption,
		deleteOption,
		restoreOption,
	],

	data() {
		return {
			pollType: this.pollType,
			drag: false,
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

		dragOptions() {
			return {
				animation: 200,
				group: 'description',
				disabled: false,
				ghostClass: 'ghost',
			}
		},

		cssVar() {
			return {
				'--content-deleted': `" (${t('polls', 'deleted')})"`,
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
