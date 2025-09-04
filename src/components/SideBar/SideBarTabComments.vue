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
import CommentsIcon from 'vue-material-design-icons/CommentProcessingOutline.vue'

import { usePollStore } from '../../stores/poll'
import { useCommentsStore } from '../../stores/comments'
import ConfigAllowComment from '../Configuration/ConfigAllowComment.vue'
import ConfigForceConfidentialComments from '../Configuration/ConfigForceConfidentialComments.vue'
import ConfigBox from '../Base/modules/ConfigBox.vue'
import PollConfigIcon from 'vue-material-design-icons/WrenchOutline.vue'
import CardDiv from '../Base/modules/CardDiv.vue'

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
	[() => pollStore.permissions.comment, () => pollStore.permissions.seeUsernames],
	() => {
		commentsStore.load()
	},
)
</script>

<template>
	<div class="comments">
		<ConfigBox
			v-if="pollStore.permissions.edit"
			:name="t('polls', 'Configuration')">
			<template #icon>
				<PollConfigIcon />
			</template>
			<ConfigAllowComment @change="pollStore.write" />
			<ConfigForceConfidentialComments @change="pollStore.write" />
			<CardDiv v-if="!pollStore.configuration.allowComment" type="warning">
				{{
					t(
						'polls',
						'Comments are disabled, except for owner and delegated poll administration.',
					)
				}}
			</CardDiv>
		</ConfigBox>

		<CommentAdd v-if="pollStore.permissions.comment" />
		<Comments v-if="!showEmptyContent" />
		<NcEmptyContent v-else v-bind="emptyContentProps">
			<template #icon>
				<CommentsIcon />
			</template>
		</NcEmptyContent>
	</div>
</template>
