<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif\UrlLoader;

/**
 * The interface for loading URL referenced content for LDIF values.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface UrlLoaderInterface
{
    /**
     * The http URL type.
     */
    const TYPE_HTTP = 'http';

    /**
     * The https URL type.
     */
    const TYPE_HTTPS = 'https';

    /**
     * The file URL type.
     */
    const TYPE_FILE = 'file';

    /**
     * Given a URL return the requested data.
     *
     * @param string $url
     * @return string
     */
    public function load($url);
}
