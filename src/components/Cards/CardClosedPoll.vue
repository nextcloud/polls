<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import CardDiv from '../Base/modules/CardDiv.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'

const pollStore = usePollStore()
const cardType = 'warning'
</script>

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'This poll is closed.') }}
		<span v-if="!pollStore.permissions.edit">
			{{ t('polls', 'No further action is possible.') }}
		</span>
	</CardDiv>
</template>
