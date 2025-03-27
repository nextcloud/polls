<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { CardDiv } from '../../Base/index.ts'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

const cardType = 'warning'
const pollStore = usePollStore()
</script>

<template>
	<CardDiv v-if="pollStore.status.isRealAnonymous" type="info">
		{{
			t(
				'polls',
				'This poll is an anonymous poll for everyone, including the owner. Deanonymizing is disabled for this poll.',
			)
		}}
		{{
			t(
				'polls',
				'But be aware that your name is not stored in an encrypted or obfuscated way.',
			)
		}}
	</CardDiv>
	<CardDiv v-else :type="cardType">
		{{
			t(
				'polls',
				'This poll is an anonymous poll for everyone, except for the owner and delegated poll administration.',
			)
		}}
		{{
			t(
				'polls',
				'Anonymization of this poll can be removed at any time by the owner and delegated poll administration.',
			)
		}}
	</CardDiv>
</template>
