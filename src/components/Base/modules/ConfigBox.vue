<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import InformationIcon from 'vue-material-design-icons/InformationVariant.vue'

interface Props {
	name: string
	info?: string
	indented?: boolean
}

const { name, info = '', indented = false } = defineProps<Props>()
</script>

<template>
	<div class="config-box">
		<div class="config-box__header">
			<slot name="icon" />
			<div :title="info" :class="['config-box__title', { indented }]">
				{{ name }}
				<InformationIcon v-if="info" />
			</div>
			<slot name="actions" />
		</div>
		<div class="config-box__container">
			<slot />
		</div>
	</div>
</template>

<style lang="scss">
.config-box__header {
	display: flex;
	align-content: center;
	align-items: center;
	gap: 5px;
	margin: 8px 0 8px 0;
}

.config-box {
	display: flex;
	flex-direction: column;
	padding: 8px 0;
	.icon-container {
		width: 20px;
	}

	.config-box__title {
		display: flex;
		flex: 1;
		opacity: 0.7;
		font-weight: bold;
		margin: 0;
	}

	.config-box__container {
		display: flex;
		flex-direction: column;
		padding-inline-start: 24px;
	}
}

.indented {
	margin-inline-start: 24px !important;
}
</style>
