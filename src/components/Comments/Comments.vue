<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import CommentItem from './CommentItem.vue'

import { usePreferencesStore } from '../../stores/preferences'
import { useCommentsStore } from '../../stores/comments'

const commentsStore = useCommentsStore()
const preferencesStore = usePreferencesStore()
const cssVar = {
	'--content-deleted': `"(${t('polls', 'deleted')})"`,
}
const alternativestyle = preferencesStore.user.useCommentsAlternativeStyling
</script>

<template>
	<TransitionGroup
		tag="ul"
		name="list"
		:class="['comments', { alternativestyle }]"
		:style="cssVar">
		<CommentItem
			v-for="comment in commentsStore.groupedComments"
			:key="comment.id"
			:comment="comment"
			tag="li" />
	</TransitionGroup>
</template>
