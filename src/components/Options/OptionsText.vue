<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :style="cssVar">
		<OptionsTextAdd v-if="!pollStore.isClosed" />
		<draggable v-if="optionsStore.list.length"
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
						<OptionItemOwner v-if="pollStore.permissions.addOptions"
							:avatar-size="16"
							:option="option"
							class="owner" />
					</template>
					<template v-if="pollStore.permissions.edit" #actions>
						<NcActions v-if="!pollStore.isClosed" class="action">
							<NcActionButton v-if="!option.deleted"
								:name="t('polls', 'Delete option')"
								:aria-label="t('polls', 'Delete option')"
								@click="optionsStore.delete(option)">
								<template #icon>
									<DeleteIcon />
								</template>
							</NcActionButton>
							<NcActionButton v-if="option.deleted"
								:name="t('polls', 'Restore option')"
								:aria-label="t('polls', 'Restore option')"
								@click="optionsStore.restore(option)">
								<template #icon>
									<RestoreIcon />
								</template>
							</NcActionButton>
							<NcActionButton v-if="!option.deleted && !pollStore.isClosed"
								:name="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
								:aria-label="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
								type="tertiary"
								@click="optionsStore.confirm(option)">
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
import { mapStores } from 'pinia'
import { NcActions, NcActionButton, NcEmptyContent } from '@nextcloud/vue'
import draggable from 'vuedraggable'
import OptionItem from './OptionItem.vue'
import OptionItemOwner from '../Options/OptionItemOwner.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import OptionsTextAdd from './OptionsTextAdd.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore } from '../../stores/options.ts'

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

	data() {
		return {
			pollType: 'textPoll',
			drag: false,
		}
	},

	computed: {
		...mapStores(useOptionsStore, usePollStore),

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
				return this.optionsStore.list
			},
			set(value) {
				this.optionsStore.reorder(value)
			},
		},
	},
	methods: {
		t,
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
