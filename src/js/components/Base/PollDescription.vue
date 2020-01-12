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
	<!-- eslint-disable-next-line vue/singleline-html-element-content-newline -->
	<h3 v-if="poll.description" class="poll-description">{{ poll.description }}</h3>
	<!-- eslint-disable-next-line vue/singleline-html-element-content-newline -->
	<h3 v-else class="poll-description">{{ t('polls', 'No description provided') }}</h3>
</template>

<script>
import { mapState } from 'vuex'

export default {
	name: 'PollDescription',

	computed: {
		...mapState({
			poll: state => state.poll
		})

	}

}
</script>

<style lang="scss" scoped>
	.poll-description {
		white-space: break-spaces;
		margin: 8px 0;
	}
</style>
