<?php

namespace OCA\Polls\Tests\Unit;

use League\FactoryMuffin\FactoryMuffin as OriginalFactoryMuffin;
use OCP\AppFramework\Db\Entity;

class FactoryMuffin extends OriginalFactoryMuffin {
	/**
	 * Generate and set the model attributes.
	 * NOTE: Patch the original method to support dynamic setter and getter
	 *        of the OCP\AppFramework\Db\Entity class
	 *
	 * @param object $model The model instance.
	 * @param array  $attr  The model attributes.
	 *
	 * @return void
	 */
	protected function generate($model, array $attr = []) {
		foreach ($attr as $key => $kind) {
			$value = $this->factory->generate($kind, $model, $this);

			$setter = 'set' . ucfirst(static::camelize($key));
			// check if there is a setter and use it instead
			if ($model instanceof Entity && is_callable([$model, $setter])) {
				$model->$setter($value);
			} elseif (method_exists($model, $setter) && is_callable([$model, $setter])) {
				$model->$setter($value);
			} else {
				$model->$key = $value;
			}
		}
	}
}
