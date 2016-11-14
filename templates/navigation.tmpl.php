<?php
//    use OCP\User;
  //  $userId = $_['userId'];
//    $userMgr = $_['userMgr'];
    $urlGenerator = $_['urlGenerator'];
?>

<div id="app-navigation">
  <a href="<?php p($urlGenerator->linkToRoute('polls.page.create_poll')); ?>"
     class="events--button button btn" type="button"
     id="new-poll-button">+ <?php p($l->t('Create new Poll')); ?>
  </a>

  <ul class="app-navigation-list">
    <?php foreach ($_['polls'] as $poll) :
      if (!userHasAccess($poll, $userId)) continue;
      // direct url to poll
      $pollUrl = $urlGenerator->linkToRoute('polls.page.goto_poll', array('hash' => $poll->getHash()));
    ?>
    <li class="app-navigation-list-item">
      <a class="action permanent" href="<?php p($pollUrl); ?>"><?php p($poll->getTitle()); ?></a>
      <span class="utils">
        <span class="action">
		      <span class="icon-more" href="#" on-toggle-show="#more-actions-34" title="<?php p($l->t('more')); ?>"></span>
	      </span>
      </span>
    </li>
    <?php endforeach; ?>
  </ul>
<!--<div id="more-actions-34" class="app-navigation-entry-menu hidden" style="display: block;">
<ul>
<li >
  <button >
    <span class="icon-share svg"></span>
    <span>Bearbeiten</span>
  </button>
</li>
<li >
  <button >
    <span class="icon-rename svg"></span>
    <span>Bearbeiten</span>
  </button>
</li>


  <button ng-click="remove(item)">
    <span class="icon-delete svg"></span>
    <span>Löschen</span>
  </button>
</li>
</ul>
</div> -->
</div>
