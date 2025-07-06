<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import CommentAdd from '../Comments/CommentAdd.vue'
import Comments from '../Comments/Comments.vue'
import CommentsIcon from 'vue-material-design-icons/CommentProcessing.vue'

import { usePollStore } from '../../stores/poll.ts'
import { useCommentsStore } from '../../stores/comments.ts'
import { Event } from '../../Types/index.ts'

const pollStore = usePollStore()
const commentsStore = useCommentsStore()

const emptyContentProps = {
	name: t('polls', 'No comments'),
	description: t('polls', 'Be the first.'),
}

const showEmptyContent = computed(() => commentsStore.comments.length === 0)

onMounted(() => {
	subscribe(Event.UpdateComments, () => commentsStore.load())
})

onUnmounted(() => {
	unsubscribe(Event.UpdateComments, () => commentsStore.load())
})
</script>

<template>
	<div class="comments">
		<CommentAdd v-if="pollStore.permissions.comment" />
		<Comments v-if="!showEmptyContent" />
		<NcEmptyContent v-else v-bind="emptyContentProps">
			<template #icon>
				<CommentsIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>
