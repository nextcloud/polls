<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import CardDiv from '../Base/modules/CardDiv.vue'
import { t } from '@nextcloud/l10n'

const route = useRoute()
const cardType = 'warning'

const cardText = computed(() =>
	route.meta.publicVotePage
		? t(
				'polls',
				'This share is locked and allows only read access. Registering is not possible.',
			)
		: t('polls', 'Voting is locked and you have just read access to this poll.'),
)
</script>

<template>
	<CardDiv :type="cardType">
		{{ cardText }}
	</CardDiv>
</template>
