<?php
/*
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Event;

use OCP\EventDispatcher\Event;
use OCA\Polls\Db\Poll;

abstract class PollEvent extends Event {
    private $poll;

    public function __construct(Poll $poll) {
        parent::__construct();
        $this->poll = $poll;
    }

    public function getPoll(): Poll {
        return $this->poll;
    }

    public function getPollId(): int {
        return $this->poll->getId();
    }

    public function getLogMsg(): string {
        return '';
    }

    public function getActor(): string {
        return \OC::$server->getUserSession()->getUser()->getUID();
    }

}
