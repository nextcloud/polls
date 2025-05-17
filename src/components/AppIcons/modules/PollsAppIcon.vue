<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { IconProps } from './types'

const {
	title = t('polls', 'Polls'),
	fillColor = 'currentColor',
	size = 24,
} = defineProps<IconProps>()
</script>

<template>
	<span
		:aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon polls-app-icon"
		role="img">
		<svg
			:fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 32 32">
			<g :fill="fillColor">
				<rect y="2" x="3" height="26" width="7" />
				<rect y="12" x="12" height="16" width="7" />
				<rect y="8" x="21" height="20" width="7" />
				<title v-if="title">{{ title }}</title>
			</g>
		</svg>
	</span>
</template>
