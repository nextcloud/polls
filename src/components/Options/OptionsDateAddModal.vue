<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import OptionsDateAddDialog from './OptionsDateAddDialog.vue'
	import OptionsDate from './OptionsDate.vue'
	import { t } from '@nextcloud/l10n';
</script>

<template>
	<div class="screen-container">
		<div class="edit-container">
			<OptionsDateAddDialog />
		</div>
		<div class="info-container">
			<h2>{{ t('polls', 'Existing options') }}</h2>
			<OptionsDate />
		</div>
	</div>
</template>

<style lang="scss" scoped>
	.screen-container {
		display: flex;
		flex-wrap: wrap;
		column-gap: 1rem;
		overflow:hidden;

		&>div {
			border-top: 1px solid;
			border-right: 1px solid;
			margin-top: -2px;
			margin-right: -2px;
			padding: 0 1rem;
		}

		.edit-container {
			flex: 2 1 18rem;
		}

		.info-container {
			flex: 1 0 7rem;
			min-width: fit-content;
		}
	}
</style>
