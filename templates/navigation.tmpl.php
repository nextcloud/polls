<div id="app-navigation">
  <a href="<?php p($urlGenerator->linkToRoute('polls.page.create_poll')); ?>" class="events--button button btn" type="button"" id="new-poll-button" type="button">+ <?php p($l->t('Create new Poll')); ?></a>
  <ul class="app-navigation-list">
    <?php foreach ($_['polls'] as $poll) : ?>
      <li class="app-navigation-list-item">
        <a class="action permanent" href="<?php p($pollUrl); ?>"><?php p($poll->getTitle()); ?></a>

        <span class="utils">
          <span class="action">
          <span class="permanent icon-share"></span>
        </span>
	<span class="action" ng-class="{'withitems': item.calendar.isShared()}">

		<!-- Add a label if the calendar has shares -->
		<!-- ngIf: item.calendar.isShared() && item.calendar.isShareable() -->
	</span>
	<span class="action">
		<span class="icon-more ng-isolate-scope" href="#" on-toggle-show="#more-actions-34" title="Mehr">
		</span>
	</span>
</span>


      </li>
      <?php endforeach; ?>

  </ul>
</div>
