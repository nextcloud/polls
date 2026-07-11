<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Share } from '../../stores/shares.types'
import type { AvatarTypeIcon } from '../User/UserAvatar.types'

import { t } from '@nextcloud/l10n'
import { computed, onMounted, ref } from 'vue'
import ContactIcon from 'vue-material-design-icons/CardAccountDetailsOutline.vue'
import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import PollGroupIcon from 'vue-material-design-icons/CodeBraces.vue'
import EmailIcon from 'vue-material-design-icons/EmailOutline.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'
import ShareIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import AdminIcon from 'vue-material-design-icons/ShieldCrownOutline.vue'
import UserItem from '../User/UserItem.vue'
import ShareMenu from './ShareMenu.vue'

const { share, tag = 'div' } = defineProps<{ share: Share; tag?: string }>()

const emit = defineEmits(['showQrCode'])

const publicShareDescription = computed(() => {
	if (share.displayName === '') {
		return t('polls', 'Token: {token}', { token: share.user.id })
	}
	return t('polls', 'Public link: {token}', { token: share.user.id })
})

const typeIconSize = 16

const userDescription = computed(() => {
	if (share.groupId && !share.pollId) {
		return t('polls', 'Poll group access')
	}
	if (share.deleted) {
		return t('polls', '(deleted)')
	}
	if (share.locked) {
		return t('polls', '(locked)')
	}
	if (share.type === 'public') {
		return publicShareDescription.value
	}
	if (share.type === 'group') {
		return t('polls', 'Group share')
	}
	if (share.type === 'contactGroup') {
		return t('polls', 'Resolve contact group first!')
	}
	if (share.type === 'circle') {
		return t('polls', 'Resolve this team first!')
	}

	return ''
})

const label = ref({
	inputValue: '',
	inputProps: {
		success: false,
		error: false,
		showTrailingButton: true,
		labelOutside: false,
		label: t('polls', 'Share label'),
	},
})

const computedTypeIcon = computed<AvatarTypeIcon>(() => {
	if (share.groupId && !share.pollId) {
		return 'pollGroupIcon'
	}
	return share.type
})

const typeIconComponent = computed(() => {
	switch (computedTypeIcon.value) {
		case 'admin':
			return AdminIcon
		case 'contact':
			return ContactIcon
		case 'email':
			return EmailIcon
		case 'external':
			return ShareIcon
		case 'pollGroupIcon':
			return PollGroupIcon
		default:
			return null
	}
})

const votedIndicator = computed(() => {
	if (share.groupId || ['public', 'group'].includes(share.type)) {
		return { class: 'vote-status empty', component: 'div', name: '' }
	}
	if (share.voted) {
		return {
			class: 'vote-status voted',
			component: VotedIcon,
			name: t('polls', 'Has voted'),
		}
	}

	return {
		class: 'vote-status unvoted',
		component: UnvotedIcon,
		name: t('polls', 'Has not voted'),
	}
})

onMounted(() => {
	label.value.inputValue = share.label
})
</script>

<template>
	<UserItem
		:tag="tag"
		:class="{ deleted: share.deleted }"
		:user="share.user"
		showEmail
		:description="userDescription"
		:label="share.label">
		<template #typeIcon>
			<component
				:is="typeIconComponent"
				v-if="typeIconComponent"
				:size="typeIconSize" />
		</template>

		<template #status>
			<component
				:is="votedIndicator.component"
				v-if="votedIndicator.component"
				:class="votedIndicator.class"
				:title="votedIndicator.name" />
		</template>

		<ShareMenu :share="share" @showQrCode="emit('showQrCode')" />
	</UserItem>
</template>

<style lang="scss">
.deleted .user-item .description {
	color: var(--color-error-text);
}

.vote-status {
	width: 32px;

	&.voted {
		color: var(--color-polls-foreground-yes);
	}

	&.unvoted {
		color: var(--color-polls-foreground-no);
	}
}
</style>
