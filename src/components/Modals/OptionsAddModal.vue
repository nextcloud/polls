<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref, onMounted, onUnmounted } from 'vue'
	import { t } from '@nextcloud/l10n';
	import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
	import NcModal from '@nextcloud/vue/components/NcModal'
	import { subscribe, unsubscribe } from '@nextcloud/event-bus'

	import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

	import OptionsDateAddDialog from '../Options/OptionsDateAddDialog.vue'
	import OptionsDate from '../Options/OptionsDate.vue'

	import { usePollStore } from '../../stores/poll';

	const pollStore = usePollStore()
	const showModal = ref(false)
	const caption = t('polls', 'Add date option')

	onMounted(() => {
		subscribe('polls:options:add-date', () => {
			showModal.value = true
		})
	})

	onUnmounted(() => {
		unsubscribe('polls:options:add-date', () => {
			showModal.value = false
		})
	})

</script>

<template>
	<NcModal v-model:show="showModal" :name="caption" size="large">
		<div class="screen-container">
			<div v-if="!pollStore.isClosed" class="edit-container">
				<OptionsDateAddDialog />
			</div>
			<NcEmptyContent v-else
				:name="t('polls', 'This poll is closed.')"
				:description="t('polls', 'Adding options is disabled')">
				<template #icon>
					<DatePollIcon />
				</template>
			</NcEmptyContent>
			<div class="info-container">
				<h2>{{ t('polls', 'Existing options') }}</h2>
				<OptionsDate />
			</div>
		</div>
	</NcModal>
</template>

<style lang="scss">
	.screen-container {
		display: flex;
		flex-wrap: wrap;
		column-gap: 1rem;
		overflow:hidden;
		padding-top: 3rem;
		padding-bottom: 1rem;

		h2 {
			display: inline-block;
		}

		&>div {
			// border-top: 1px solid;
			// border-right: 1px solid;
			// margin-top: -2px;
			// margin-right: -2px;
			padding: 0 1rem;
		}

		.edit-container {
			flex: 2 1 22rem;
			// max-width: 42rem;
			> div {
				background-color: var(--container-background-light);
				padding: 0 1rem;
			}
			> div:first-child {
				border-radius: var(--border-radius-container-large) var(--border-radius-container-large) 0 0;
			}
			> div:last-child {
				border-radius: 0 0 var(--border-radius-container-large) var(--border-radius-container-large);
			}
			.header-container {
				padding-top: 0;
			}
			// .preview-container {
			// 	padding-top: 24px;
			// }
		}

		.info-container {
			flex: 1 0 10rem;
			min-width: fit-content;
			// max-width: 18rem;
			.menu-wrapper {
				flex: 0 44px;
			}
		}
	}
</style>
