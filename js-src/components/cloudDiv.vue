/* global Vue, oc_userconfig */
<template>
	<div class="cloud">
		<span class="expired" v-if="options.expired">{{ t('polls', 'Expired')}} </span>
		<span class="open" v-if="options.expiration">{{ t('polls', 'Expires on %n', 1, expirationdate) }}</span>
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
				var date = moment(this.options.expire);
				console.log(this.options.expire)
				console.log(date)
				console.log(OC.getLocale())
				date.locale(OC.getLocale());
				console.log(date)
				return date
			}
		}
	}
</script>

<style scoped>
</style>
