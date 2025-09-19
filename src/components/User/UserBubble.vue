<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'

import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'

import { createDefault, type User } from '../../Types'

interface Props {
	user: User
}

const { user = createDefault<User>() } = defineProps<Props>()

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
