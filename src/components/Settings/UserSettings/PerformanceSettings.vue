<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { InputDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'

import { usePreferencesStore } from '../../../stores/preferences.ts'

import CardDiv from '../../Base/modules/CardDiv.vue'

const preferencesStore = usePreferencesStore()

const cardType = 'info'
</script>

<template>
	<div class="user_settings">
		<InputDiv
			v-model.lazy="preferencesStore.user.performanceThreshold"
			type="number"
			inputmode="numeric"
			use-num-modifiers
			:placeholder="'1000'"
			:modifier-step-value="100"
			:num-min="200"
			@change="preferencesStore.write()" />
		<CardDiv :type="cardType">
			{{
				t(
					'polls',
					'A poll with many options and voters can have a heavy impact on client performance.',
				)
			}}
			{{
				t(
					'polls',
					'Set the amount of voting cells (options x participants) up to which all voting cells should be displayed.',
				)
			}}
			{{
				t(
					'polls',
					'If this threshold gets trespassed, only the current participant will be displayed, to avoid a performance breakdown.',
				)
			}}
			{{
				t(
					'polls',
					'The default threshold of 1000 should be a good and safe value.',
				)
			}}
		</CardDiv>
	</div>
</template>
