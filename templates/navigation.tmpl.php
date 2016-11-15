<?php
//    use OCP\User;
  //  $userId = $_['userId'];
//    $userMgr = $_['userMgr'];
    $urlGenerator = $_['urlGenerator'];

    //Popover with Boostrap and template: http://fiddle.jshell.net/J7nDz/5/light/
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
    <li id="menuList" class="app-navigation-list-item">
      <a class="action permanent" href="<?php p($pollUrl); ?>"><?php p($poll->getTitle()); ?></a>
      <span class="utils">
        <span class="action">
		    <!--  <span class="icon-more" href="#" on-toggle-show="#more-actions-34" title="<?php //p($l->t('more')); ?>"></span> -->
          <a tabindex="0" data-toggle="poll3dot" data-container="body" data-placement="bottom" data-trigger="focus"  type="button" href="#">
            <span class="icon-more"></span>
          </a>
	      </span>
      </span>
    </li>
    <?php endforeach; ?>
    <li><button type="button" class="btn btn-default" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Vivamus
sagittis lacus vel augue laoreet rutrum faucibus.">
  Popover on bottom
</button></li>
  </ul>
</div>
<!--
<div class="popovermenu bubble open menu">
  <ul>
    <li><a href="#" class="menuitem action action-details permanent" data-action="Details"><span class="icon icon-details"></span><span>Details</span></a></li>
    <li><a href="#" class="menuitem action action-rename permanent" data-action="Rename"><span class="icon icon-rename"></span><span>Umbenennen</span></a></li>
    <li><a href="#" class="menuitem action action-download permanent" data-action="Download"><span class="icon icon-download"></span><span>Herunterladen</span></a></li>
    <li><a href="#" class="menuitem action action-delete permanent" data-action="Delete"><span class="icon icon-delete"></span><span>Löschen</span></a></li>
  </ul>
</div>
-->
