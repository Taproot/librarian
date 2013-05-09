<?php

namespace Taproot\Librarian\Index;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface IndexInterface extends EventSubscriberInterface {
	public function setConnection(Connection $conn);
}
