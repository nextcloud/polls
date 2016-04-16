<?php
\OCP\Util::addStyle('polls', 'main');
?>
<div id="app">
    <div id="app-content">
        <div id="app-content-wrapper">
            <header>
                <div class="row">
                    <div class="col-100">
                        <h1>
                            <?php p($l->t('Access denied')); ?>
                        </h1>
                        <h2>
                            <?php p($l->t('You are not allowed to view this poll or the poll does not exist.')); ?>
                        </h2>
                    </div>
                </div>
            </header>
        </div>
    </div>
</div>
