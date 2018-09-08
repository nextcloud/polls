<template>
	<div class="close flex-row">
		<a id="closeDetails" @:click="hideSidebar" class="close icon-close has-tooltip-bottom" :title="closeDetailLabel" href="#" :alt="closeDetailLabelAlt"></a>
	</div>
</template>

<script>
	export default {
		data: function () {
			return {
				closeDetailLabel: t('Close details'),
				closeDetailLabelAlt: t('Close')
			};
		},
		methods: {
			hideSidebar: function () {
				OC.Apps.hideAppSidebar();
			}
		}
	}
</script>

