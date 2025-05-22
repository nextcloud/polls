<?php
namespace OCA\Polls\Listener;

use OCA\Polls\AppInfo\Application;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class PollsReferenceListener implements IEventListener {
    public function handle(Event $event): void {
        if (!$event instanceof RenderReferenceEvent) {
            return;
        }

        Util::addScript(Application::APP_ID, 'polls-reference');
    }
}