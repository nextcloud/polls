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

/* global Vue, oc_userconfig */
<template>
	<div class="cloud">
		<span class="expired" v-if="options.expired">{{ t('polls', 'Expired')}} </span>
		<span class="open" v-if="options.expiration">{{ t('polls', 'Expires %n', 1, expirationdate) }}</span>
		<span class="open" v-else>{{ t('polls', 'Expires never') }}</span>

		<span class="information">{{ options.access }}</span>
		<span class="information" v-if="options.isAnonymous"> {{ t('polls', 'Anonymous poll') }}</span>
		<span class="information" v-if="options.fullAnonymous"> {{ t('polls', 'Usernames hidden to Owner') }}</span>
		<span class="information" v-if="options.isAnonymous & !options.fullAnonymous"> {{ t('polls', 'Usernames visible to Owner') }}</span>
	</div>
</template>

<script>
	export default {
		props: ['options', ],
		
		computed: {
			expirationdate: function() {
				var date = moment(this.options.expirationDate, moment.localeData().longDateFormat('L')).fromNow()
				return date
			}
		}
	}
</script>

<style scoped>
</style>
