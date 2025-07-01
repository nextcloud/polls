<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { t } from '@nextcloud/l10n'

import VotedIcon from 'vue-material-design-icons/CheckboxMarked.vue'
import UnvotedIcon from 'vue-material-design-icons/MinusBox.vue'

import UserItem from '../User/UserItem.vue'

import { Share, ShareType } from '../../stores/shares.ts'
import ShareMenu from './ShareMenu.vue'

const emit = defineEmits(['showQrCode'])

const { share } = defineProps<{ share: Share }>()

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

const userItemProps = computed(() => ({
	user: share.user,
	label: share.label,
	showEmail: true,
	resolveInfo: true,
	forcedDescription: share.deleted ? `(${t('polls', 'deleted')})` : null,
	showTypeIcon: true,
	icon: true,
}))

onMounted(() => {
	label.value.inputValue = share.label
})
</script>

<template>
	<div :class="{ deleted: share.deleted }">
		<UserItem
			v-bind="userItemProps"
			:delegated-from-group="!share.pollId"
			:deleted-state="share.deleted"
			:locked-state="share.locked">
			<template #status>
				<div v-if="share.voted">
					<VotedIcon
						class="vote-status voted"
						:name="t('polls', 'Has voted')" />
				</div>
				<div
					v-else-if="
						share.groupId
						|| [ShareType.Public, ShareType.Group].includes(share.type)
					">
					<div class="vote-status empty" />
				</div>
				<div v-else>
					<UnvotedIcon
						class="vote-status unvoted"
						:name="t('polls', 'Has not voted')" />
				</div>
			</template>

			<ShareMenu :share="share" @show-qr-code="emit('showQrCode')" />
		</UserItem>
	</div>
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
