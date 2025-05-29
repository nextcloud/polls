<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="flex_settings">
		<slot name="default" />
	</div>
</template>

<style lang="scss">
.flex_settings {
	display: flex;
	flex-wrap: wrap;
	align-items: stretch;

	.settings-section {
		flex: 1 0 480px;
		margin-bottom: 0;
		border-bottom: 1px solid var(--color-border);
	}
}

.settings-description {
	margin-top: -0.2em;
	margin-bottom: 1em;
	opacity: 0.7;
}

.user_settings {
	padding-top: 8px;

	textarea {
		width: 99%;
		resize: vertical;
		height: 230px;
	}
}

.settings_details {
	padding-bottom: 16px;
	margin-inline-start: 36px;
}
</style>
