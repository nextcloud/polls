<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'
import { PollsAppIcon } from '../components/AppIcons'

type RichObject = {
	id: number
	poll: {
		id: number
		title: string
		description: string
		ownerDisplayName: string
		ownerId: string
		url: string
	}
}

interface Props {
	richObjectType: string
	richObject?: RichObject
	accessible?: boolean
}
const {
	richObjectType = 'poll',
	richObject = null,
	accessible = true,
} = defineProps<Props>()
</script>

<template>
	<div v-if="richObject" class="polls_widget">
		<div class="widget_header">
			<PollsAppIcon :size="20" class="title-icon" />
			<span class="title">
				{{ richObject.poll.title }}
			</span>
		</div>
		<div class="description">
			<span class="clamped">
				{{ richObject.poll.description }}
			</span>
		</div>
		<div class="owner">
			<NcUserBubble
				:user="richObject.poll.ownerId"
				:display-name="richObject.poll.ownerDisplayName" />
		</div>
	</div>
</template>

<style lang="scss" scoped>
.polls_widget {
	padding: 0.6rem;
}
.widget_header {
	display: flex;
}

.polls_app_icon {
	flex: 0 0 1.4rem;
}
.title {
	flex: 1;
	font-weight: bold;
	padding-left: 0.6rem;
}
.description {
	margin-left: 1.4rem;
	padding: 0.6rem;
}
.owner {
	margin-left: 1.4rem;
	padding-left: 0.6rem;
}
.clamped {
	display: -webkit-box !important;
	-webkit-line-clamp: 4;
	line-clamp: 4;
	-webkit-box-orient: vertical;
	text-wrap: wrap;
	overflow: clip !important;
	text-overflow: ellipsis !important;
	padding: 0 !important;
}
</style>
