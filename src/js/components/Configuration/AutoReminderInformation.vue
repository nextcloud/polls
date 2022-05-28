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

<template lang="html">
	<div class="auto-reminder-information">
		<p> {{ t('polls', 'The automatic reminder is sent to all shares via email:') }} </p>
		<p> 1. {{ t('polls', 'For polls with expiration:') }} </p>
		<ul>
			<li> - {{ t('polls', '48 hours before the expiration date, if the poll is created more than 5 days before the expiration.') }}</li>
			<li> - {{ t('polls', '36 hours before the expiration date, if the poll is created more than 2 and less than 5 days before the expiration.') }}</li>
		</ul>
		<p> &nbsp; </p>
		<p> 2. {{ t('polls', 'For date polls without expiration:') }} </p>
		<ul>
			<li> - {{ t('polls', '48 hours before the first date option, if the poll is created more than 5 days before the first date option.') }}</li>
			<li> - {{ t('polls', '36 hours before the first date option, if the poll is created more than 2 and less than 5 days before the first date option.') }}</li>
		</ul>
		<p> &nbsp; </p>
		<p> 3. {{ t('polls', 'No reminder is sent:') }} </p>
		<ul>
			<li> - {{ t('polls', 'For text polls without expiration.') }}</li>
			<li> - {{ t('polls', 'For polls created less than 2 days before the expiration.') }}</li>
			<li> - {{ t('polls', 'If a share already got a reminder (no matter, if the expiration date got changed).') }}</li>
			<li> - {{ t('polls', 'If the poll is already closed.') }}</li>
		</ul>
	</div>
</template>

<script>

export default {
	name: 'AutoReminderInformation',
}
</script>
<style lang="scss">
	.auto-reminder-information {
		padding: 8px;
	}

</style>
