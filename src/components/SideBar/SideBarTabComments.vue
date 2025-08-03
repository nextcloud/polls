<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { onBeforeRouteLeave, onBeforeRouteUpdate } from 'vue-router'
import { t } from '@nextcloud/l10n'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import CommentAdd from '../Comments/CommentAdd.vue'
import Comments from '../Comments/Comments.vue'
import CommentsIcon from 'vue-material-design-icons/CommentProcessing.vue'

import { usePollStore } from '../../stores/poll'
import { useCommentsStore } from '../../stores/comments'
import { Logger } from '../../helpers'

const pollStore = usePollStore()
const commentsStore = useCommentsStore()

const emptyContentProps = {
	name: t('polls', 'No comments'),
	description: t('polls', 'Be the first.'),
}

const showEmptyContent = computed(() => commentsStore.comments.length === 0)

onMounted(() => {
	commentsStore.load()
})

onBeforeRouteUpdate(async () => {
	commentsStore.load()
})

onBeforeRouteLeave(() => {
	commentsStore.$reset()
})

watch(
	[() => pollStore.permissions.comment, () => pollStore.configuration.anonymous],
	([commentNew, commentOld], [anonymousNew, anonymousOld]) => {
		Logger.debug('Configuration affecting comments changed', {
			comment: `${commentOld} → ${commentNew}`,
			anonymous: `${anonymousOld} → ${anonymousNew}`,
		})
		commentsStore.load()
	},
)
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
