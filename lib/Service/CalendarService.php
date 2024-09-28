<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Service;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Preferences;
use OCA\Polls\Model\CalendarEvent;
use OCA\Polls\UserSession;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager as CalendarManager;
use Psr\Log\LoggerInterface;

class CalendarService {
	/** @var ICalendar[] */
	private array $calendars;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private CalendarManager $calendarManager,
		private OptionMapper $optionMapper,
		private Preferences $preferences,
		private PreferencesService $preferencesService,
		private UserSession $userSession,
		private LoggerInterface $logger,
	) {
		$this->setUp();
	}

	/**
	 * setUp
	 */
	private function setUp(): void {
		$this->preferences = $this->preferencesService->get();
		$this->getCalendarsForPrincipal();
	}

	/**
	 * getCalendars -
	 */
	private function getCalendarsForPrincipal(): void {
		$principalUri = $this->userSession->getUser()->getPrincipalUri();

		if (!empty($principalUri)) {
			$this->calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);
		} else {
			$this->calendars = [];
		}
	}


	/**
	 * getTimerange - set timeranges to search within based on the option's time information
	 *
	 * @return DateTimeImmutable[]
	 *
	 * @psalm-return array{from: DateTimeImmutable, to: DateTimeImmutable}
	 */
	private function getTimerange(int $optionId, DateTimeZone $timezone): array {
		$option = $this->optionMapper->find($optionId);
		$searchIntervalBefore = new DateInterval('PT' . $this->preferences->getCheckCalendarsHoursBefore() . 'H');
		$searchIntervalAfter = new DateInterval('PT' . $this->preferences->getCheckCalendarsHoursAfter() . 'H');

		$from = (new DateTime())
			->setTimeZone($timezone)
			->setTimestamp($option->getTimestamp())
			->sub($searchIntervalBefore);
		$to = (new DateTime())
			->setTimeZone($timezone)
			->setTimestamp($option->getTimestamp() + $option->getDuration())
			->add($searchIntervalAfter);

		return [
			'from' => DateTimeImmutable::createFromMutable($from),
			'to' => DateTimeImmutable::createFromMutable($to),
		];
	}

	private function searchEventsByTimeRange(DateTimeImmutable $from, DateTimeImmutable $to): array {
		if ($this->userSession->getUser()->getPrincipalUri() === '') {
			return [];
		}

		$query = $this->calendarManager->newQuery($this->userSession->getUser()->getPrincipalUri());
		$query->setTimerangeStart($from);
		$query->setTimerangeEnd($to);

		foreach ($this->calendars as $calendar) {
			if (in_array($calendar->getKey(), json_decode($this->preferences->getPreferences())->checkCalendars)) {
				$query->addSearchCalendar($calendar->getUri());
			}
		}
		return $this->calendarManager->searchForPrincipal($query);
	}

	/**
	 * getEvents - get events from the user's calendars inside given timespan
	 *
	 * @return CalendarEvent[]
	 *
	 * @psalm-return list<CalendarEvent>
	 */
	public function getEvents(int $optionId): array {
		$timezone = new DateTimeZone($this->userSession->getClientTimeZone());
		$timerange = $this->getTimerange($optionId, $timezone);

		$events = [];
		$foundEvents = $this->searchEventsByTimeRange($timerange['from'], $timerange['to']);

		foreach ($foundEvents as $event) {
			$calendar = $this->getCalendarFromEvent($event);
			if ($calendar === null) {
				continue;
			}

			if (!isset($event['objects'][0])) {
				$this->logger->warning('Skipping invalid calendar entry', ['calendarEvent' => json_encode($event)]);
				continue;
			}
			
			$calendarEvent = new CalendarEvent($event, $calendar, $timerange['from'], $timerange['to'], $timezone);

			if ($calendarEvent->getOccurrences()) {
				for ($index = 0; $index < count($calendarEvent->getOccurrences()); $index++) {
					$calendarEvent->setOccurrence($index);
					array_push($events, $calendarEvent);
				}
			} else {
				array_push($events, $calendarEvent);
			}
		}
		return $events;
	}

	private function getCalendarFromEvent(array $event): ?ICalendar {
		foreach ($this->calendars as $calendar) {
			if ($calendar->getKey() === $event['calendar-key']) {
				return $calendar;
			}
		}
		return null;
	}

	/**
	 * Get user's calendars
	 *
	 * @return array[]
	 *
	 * @psalm-return list<array{calendar: ICalendar, calendarUri: string, displayColor: null|string, key: string, name: null|string, permissions: int}>
	 */
	public function getCalendars(): array {
		$calendars = [];
		foreach ($this->calendars as $calendar) {
			$calendars[] = [
				'key' => $calendar->getKey(),
				'calendarUri' => $calendar->getUri(), // since NC23
				'name' => $calendar->getDisplayName(),
				'displayColor' => $calendar->getDisplayColor(),
				'permissions' => $calendar->getPermissions(),
				'calendar' => $calendar,
			];
		}
		return $calendars;
	}
}
