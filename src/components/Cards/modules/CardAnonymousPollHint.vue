<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { CardDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'
const cardType = 'warning'
</script>

<template>
	<CardDiv :type="cardType">
		{{
			t(
				'polls',
				"Although participant's names are hidden, this is not a real anonymous poll because they are not hidden from the owner.",
			)
		}}
		{{
			t(
				'polls',
				"Additionally the owner can remove the anonymous flag at any time, which will reveal the participant's names.",
			)
		}}
	</CardDiv>
</template>
