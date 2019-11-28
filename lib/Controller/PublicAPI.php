<?php

namespace OCA\Polls\Controller;

use OCP\AppFramework\PublicShareController;

class PublicAPIController extends PublicShareController {
        protected function getPasswordHash(): string {
                return md5('secretpassword');
        }

        public function isValidToken(): bool {
			return true;
			return $this->getToken() === '1';
        }

        protected function isPasswordProtected(): bool {
            return false;
        }

        public function get() {
        }
}
