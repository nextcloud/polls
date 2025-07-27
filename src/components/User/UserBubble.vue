<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'

import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'

import type { User } from '../../Types'

interface Props {
	user: User
}

const {
	user = {
		id: '',
		displayName: '',
		emailAddress: '',
		isNoUser: true,
		isAdmin: false,
		isGuest: false,
		type: '',
		subName: null,
		subtitle: null,
		desc: null,
		organisation: null,
		languageCode: '',
		localeCode: null,
		timeZone: null,
		categories: null,
	},
} = defineProps<Props>()

const bubbleProps = computed(() => ({
	user: user.isNoUser || user.isGuest ? undefined : user.id,
	displayName:
		user.type === 'deleted'
			? t('polls', 'Deleted participant')
			: user.displayName,
}))
</script>

<template>
	<NcUserBubble v-bind="bubbleProps" />
</template>
