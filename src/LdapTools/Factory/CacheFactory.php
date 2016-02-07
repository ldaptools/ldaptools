<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Factory;

use LdapTools\Cache\DoctrineCache;
use LdapTools\Cache\NoCache;
use LdapTools\Cache\StashCache;
use LdapTools\Cache\CacheInterface;
use LdapTools\Exception\InvalidArgumentException;

/**
 * A factory for retrieving the caching mechanism by its type.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class CacheFactory
{
    /**
     * The StashPHP caching library.
     */
    const TYPE_STASH = 'stash';

    /**
     * The Doctrine Common caching library.
     */
    const TYPE_DOCTRINE = 'doctrine';

    /**
     * The NoCache class.
     */
    const TYPE_NONE = 'none';

    /**
     * Retrieve the Cache object by its configured type and options.
     *
     * @param $type
     * @param array $options
     * @return CacheInterface
     */
    public static function get($type, array $options)
    {
        if (self::TYPE_STASH == $type) {
            $cache = new StashCache();
        } elseif (self::TYPE_DOCTRINE == $type) {
            $cache = new DoctrineCache();
        } elseif (self::TYPE_NONE == $type) {
            $cache = new NoCache();
        } else {
            throw new InvalidArgumentException(sprintf('Unknown cache type "%s".', $type));
        }
        $cache->setOptions($options);

        return $cache;
    }
}
