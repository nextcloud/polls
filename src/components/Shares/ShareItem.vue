<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { t } from '@nextcloud/l10n'

import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'

import UserItem from '../User/UserItem.vue'
import ShareMenu from './ShareMenu.vue'

import type { Share } from '../../stores/shares.types'
import { AvatarTypeIcon } from '../User/UserAvatar.types'

const emit = defineEmits(['showQrCode'])

const { share, tag = 'div' } = defineProps<{ share: Share; tag?: string }>()

const publicShareDescription = computed(() => {
	if (share.displayName === '') {
		return t('polls', 'Token: {token}', { token: share.user.id })
	}
	return t('polls', 'Public link: {token}', { token: share.user.id })
})

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

const computedTypeIcon = computed<AvatarTypeIcon>(() => {
	if (share.groupId && !share.pollId) {
		return 'pollGroupIcon'
	}
	return true
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

onMounted(() => {
	label.value.inputValue = share.label
})
</script>

<template>
	<UserItem
		:tag="tag"
		:class="{ deleted: share.deleted }"
		:user="share.user"
		show-email
		:type-icon="computedTypeIcon"
		:description="userDescription"
		:label="share.label">
		<template #status>
			<div v-if="share.groupId || ['public', 'group'].includes(share.type)">
				<div class="vote-status empty" />
			</div>
			<div v-else-if="share.voted">
				<VotedIcon
					class="vote-status voted"
					:name="t('polls', 'Has voted')" />
			</div>
			<div v-else>
				<UnvotedIcon
					class="vote-status unvoted"
					:name="t('polls', 'Has not voted')" />
			</div>
		</template>

		<ShareMenu :share="share" @show-qr-code="emit('showQrCode')" />
	</UserItem>
</template>

<style lang="scss">
.deleted .user-item .description {
	color: var(--color-error-text);
}

.vote-status {
	margin-inline-start: 8px;
	width: 32px;

	&.voted {
		color: var(--color-polls-foreground-yes);
	}

	&.unvoted {
		color: var(--color-polls-foreground-no);
	}
}
</style>
