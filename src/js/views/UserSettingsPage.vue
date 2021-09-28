<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="section">
		<div class="sub-section">
			<h2>{{ t('polls', 'Polls user settings') }}</h2>
			<FeatureSettings />
		</div>

		<div class="section-wrapper">
			<div class="sub-section">
				<h2>{{ t('polls', 'Performance settings') }}</h2>
				<PerformanceSettings />
			</div>

			<div class="sub-section">
				<h2>{{ t('polls', 'Experimental styles') }}</h2>
				<StyleSettings />
			</div>
		</div>
	</div>
</template>

<script>

export default {
	name: 'UserSettingsPage',

	components: {
		FeatureSettings: () => import('../components/Settings/FeatureSettings'),
		StyleSettings: () => import('../components/Settings/StyleSettings'),
		PerformanceSettings: () => import('../components/Settings/PerformanceSettings'),
	},

	created() {
		this.$store.dispatch('settings/getCalendars')
		this.$store.dispatch('settings/get')
	},
}
</script>

<style lang="scss">
	.section {
		display: flex;
		flex-wrap: wrap;
		.section-wrapper {
			flex: 1 640px;
		}
		.sub-section {
			margin-bottom: 48px;
			flex: 1 640px;
		}

		h2 {
			margin-bottom: 0;
		}
	}
</style>
