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
	<AppContent>
		<EmptyContent icon="icon-search">
			{{ t('polls', '404 - poll not found') }}
			<template #desc>
				<p v-if="getCurrentUser()">
					{{ t('polls', 'Enter a poll or start a new one.') }}
				</p>
				<button v-else @click="gotoLogin()">
					{{ t('polls', 'Goto Nextcloud') }}
				</button>
			</template>
		</EmptyContent>
	</AppContent>
</template>

<script>
import { AppContent, EmptyContent } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'NotFound',
	components: {
		AppContent,
		EmptyContent,
	},

	methods: {
		gotoLogin() {
			window.location.replace(generateUrl('/'))
		},
	},
}
</script>
