<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Log;

use LdapTools\Operation\CacheableOperationInterface;

/**
 * A simple logger to output the actions to the console using echo.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class EchoLdapLogger implements LdapLoggerInterface
{
    /**
     * @inheritdoc
     */
    public function start(LogOperation $log)
    {
        $message = "(".$log->getDomain().") -- Start Operation Type: ".$log->getOperation()->getName().PHP_EOL;

        foreach ($log->getOperation()->getLogArray() as $key => $value) {
            $message .= "\t$key: $value".PHP_EOL;
        }

        echo $message;
    }

    /**
     * @inheritdoc
     */
    public function end(LogOperation $log)
    {
        $duration = $log->getStopTime() - $log->getStartTime();

        if ($log->getOperation() instanceof CacheableOperationInterface) {
            echo "\tCache Hit: ".var_export($log->getUsedCachedResult(), true).PHP_EOL;
        }
        if (!is_null($log->getError())) {
            echo "\tError: ".$log->getError().PHP_EOL;
        }

        echo "(".$log->getDomain().") -- End Operation Type: ".$log->getOperation()->getName()." -- ($duration seconds)".PHP_EOL;
    }
}
