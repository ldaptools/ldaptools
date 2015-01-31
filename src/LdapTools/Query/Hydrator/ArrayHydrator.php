<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query\Hydrator;

/**
 * Hydrates a LDAP entry in an easier to use array form.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ArrayHydrator implements HydratorInterface
{
    use HydratorTrait;

    /**
     * {@inheritdoc}
     */
    public function hydrateEntry(array $entry)
    {
        $attributes = [];

        foreach ($entry as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (isset($value['count']) && $value['count'] == 1) {
                $attributes[$key] = $value[0];
            } elseif (isset($value['count']) && $value['count'] > 0) {
                $attributes[$key] = [];
                for ($i = 0; $i < $value['count']; $i++) {
                    $attributes[$key][] = $value[$i];
                }
            }
        }
        $attributes = $this->setAttributesFromSchema($attributes);
        $attributes = $this->convertValuesFromLdap($attributes);

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateAll(array $entries)
    {
        $results = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $results[] = $this->hydrateEntry($entries[$i]);
        }

        return $results;
    }
}
