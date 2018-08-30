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
				var date = moment(this.options.expire, moment.localeData().longDateFormat('L')).fromNow();
				return date
			}
		}
	}
</script>

<style lang="scss" scoped>
	.cloud {
		display: flex;
		flex-wrap: wrap;
		
		> span {
			color: #fff;
			margin: 2px;
			padding: 2px 4px;
			border-radius: 3px;
			text-shadow: 1px 1px #666;
			background-color: #aaa;
			
		}
		.open {
			background-color: #49bc49;
		}
		.expired {
			background-color: #f45573;
		}
		.information {
			background-color: #b19c3e;
		}
	}

</style>
