<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\Group;

use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Model\Circle as CirclesCircle;
use OCA\Polls\Exceptions\CirclesNotEnabledException;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\User;
use OCA\Polls\Model\UserBase;

/**
 * @psalm-suppress UnusedClass
 */
class Circle extends UserBase {
	/** @var string */
	public const TYPE = 'circle';

	private CirclesCircle $circle;

	public function __construct(
		string $id,
	) {
		parent::__construct($id, self::TYPE);
		$this->description = $this->l10n->t('Circle');
		$this->richObjectType = 'circle';

		if (self::isEnabled()) {
			$this->circle = Circles::detailsCircle($id);
			$this->displayName = $this->circle->getName();
		} else {
			throw new CirclesNotEnabledException();
		}
	}

	public static function isEnabled() : bool {
		return Container::isAppEnabled('circles');
	}

	public function getRichObjectString() : array {
		return [
			'type' => $this->richObjectType,
			'id' => $this->getId(),
			'name' => $this->getDisplayName(),
			'link' => $this->circle->getUrl(),
		];
	}

	/**
	 * @return Circle[]
	 */
	public static function search(string $query = '', array $skip = []) : array {
		$circles = [];
		if (self::isEnabled()) {
			foreach (Circles::listCircles(CirclesCircle::CIRCLES_ALL, $query) as $circle) {
				if (!in_array($circle->getUniqueId(), $skip)) {
					$circles[] = new self($circle->getUniqueId());
				}
			}
		}

		return $circles;
	}

	/**
	 * @return User[]|Email[]|Contact[]
	 */
	public function getMembers(): array {
		$members = [];
		if (self::isEnabled()) {
			foreach (Circles::detailsCircle($this->id)->getMembers() as $circleMember) {
				if ($circleMember->getType() === Circles::TYPE_USER) {
					$members[] = new User($circleMember->getUserId());
				} elseif ($circleMember->getType() === Circles::TYPE_MAIL) {
					$members[] = new Email($circleMember->getUserId());
				} elseif ($circleMember->getType() === Circles::TYPE_CONTACT) {
					$members[] = new Contact($circleMember->getUserId());
				} else {
					continue;
				}
			}
		}
		return $members;
	}
}
