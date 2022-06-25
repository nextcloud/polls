<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Provider;

use OCA\Polls\Model\Acl;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IProvider;
use OCP\Collaboration\Resources\IResource;
use OCP\IUser;
use OCP\IURLGenerator;

class ResourceProvider implements IProvider {
	public const RESOURCE_TYPE = 'poll';

	/** @var Acl */
	private $acl;

	/** @var PollMapper */
	private $pollMapper;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IManager */
	private $resourceManager;

	public function __construct(
		PollMapper $pollMapper,
		Acl $acl,
		IManager $resourceManager,
		IURLGenerator $urlGenerator
	) {
		$this->pollMapper = $pollMapper;
		$this->urlGenerator = $urlGenerator;
		$this->resourceManager = $resourceManager;
		$this->acl = $acl;
	}

	public function getType(): string {
		return self::RESOURCE_TYPE;
	}

	public function getResourceRichObject(IResource $resource): array {
		$poll = $this->pollMapper->find(intval($resource->getId()));

		return [
			'type' => self::RESOURCE_TYPE,
			'id' => $resource->getId(),
			'name' => $poll->getTitle(),
			'link' => $poll->getVoteUrl(),
			'iconUrl' => $this->urlGenerator->imagePath('polls', 'polls-black.svg')
		];
	}

	public function canAccessResource(IResource $resource, ?IUser $user): bool {
		if ($resource->getType() !== self::RESOURCE_TYPE || !$user instanceof IUser) {
			return false;
		}

		try {
			$this->acl->setPollId(intval($resource->getId()));
			$poll = $this->acl->getPoll();
		} catch (NotAuthorizedException $e) {
			return false;
		} catch (DoesNotExistException $e) {
			return false;
		}

		return true;
	}

	public function invalidateAccessCache($pollId = null): void {
		if ($pollId !== null) {
			$resource = $this->resourceManager->getResourceForUser(self::RESOURCE_TYPE, (string)$pollId, null);
			$this->resourceManager->invalidateAccessCacheForResource($resource);
		} else {
			$this->resourceManager->invalidateAccessCacheForProvider($this);
		}
	}
}
