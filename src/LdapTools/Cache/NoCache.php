<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Cache;

/**
 * Provides a class that will never get/set any cached items, allowing someone to disable caching altogether.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class NoCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get($itemType, $itemName)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheableItemInterface $item)
    {
        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($type, $name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheCreationTime($itemType, $itemName)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUseAutoCache()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($itemType, $itemName)
    {
        return false;
    }
}
