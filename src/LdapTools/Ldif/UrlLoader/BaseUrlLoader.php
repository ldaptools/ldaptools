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

use LdapTools\Exception\LdifUrlLoaderException;

/**
 * Implements a LDIF value loader for some base types: file, http, https
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class BaseUrlLoader implements UrlLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($url)
    {
        $contents = @file_get_contents($url);

        if ($contents === false) {
            throw new LdifUrlLoaderException(sprintf(
                'Unable to load URL. Check the URL and your "allow_url_fopen" setting',
                $url
            ));
        }

        return $contents;
    }
}
