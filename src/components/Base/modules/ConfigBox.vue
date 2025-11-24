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
		<slot name="icon" />
		<div :title="info" :class="['config-box__title', { indented }]">
			{{ name }}
			<InformationIcon v-if="info" />
		</div>
		<slot name="actions" />
		<div class="config-box__container">
			<slot />
		</div>
	</div>
</template>

<style lang="scss">
.config-box {
	display: grid;
	grid-template-columns: auto 1fr auto;
	grid-template-areas:
		'icon title actions'
		'. container container';
	column-gap: 0.5rem;
	margin: 1rem 0;

	&__title {
		grid-area: title;
		opacity: 0.7;
		font-weight: bold;
		margin: 0.5rem 0;
	}

	&__container {
		grid-area: container;
	}

	.indented {
		margin-inline-start: 1.6rem !important;
	}
}
</style>
