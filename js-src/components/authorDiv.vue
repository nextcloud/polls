/* global Vue, oc_userconfig */
<template>
	<div>
		<div class="description leftLabel">{{ownerLabel}}</div> 
		<div class="avatar has-tooltip-bottom" style="height: 32px; width: 32px;" >
			<img :src="avatarURL" width="32" height="32">
		</div>
		<div class="author">{{displayName}}</div>
	</div>
</template>

<script>
	export default {
		data: function () {
			return {
				ownerLabel: t('polls', 'Owner'),
				displayName: OC.getCurrentUser().uid,
				avatarURL: OC.generateUrl(
					'/avatar/{user}/{size}?v={version}',
					{
						user: OC.getCurrentUser().uid,
						size: Math.ceil(32 * window.devicePixelRatio),
						version: oc_userconfig.avatar.version
				})
			};
		}
	}
</script>
