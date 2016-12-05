<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Hydrator;

use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Utilities\LdapUtilities;
use LdapTools\Utilities\MBString;

/**
 * Some common functions needed in both the operation hydrator and the LdapQuery. Need to completely separate them at
 * some point. This is mostly for logic relating to parsing out different sets of attributes based on alias and schema
 * information.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait HydrateQueryTrait
{
    /**
     * If any attributes that were requested to be ordered by are not explicitly in the attribute selection, add them.
     *
     * @param array $attributes
     * @param null|string $alias
     * @return array
     */
    protected function mergeOrderByAttributes(array $attributes, $alias = null)
    {
        if (!$this->isWildCardSelection() && !empty($this->orderBy)) {
            $orderBy = $this->getAttributesForAlias(array_keys($this->orderBy), $alias);
            foreach ($orderBy as $attribute) {
                if (!in_array(MBString::strtolower($attribute), MBString::array_change_value_case($attributes))) {
                    $attributes[] = $attribute;
                }
            }
        }

        return $attributes;
    }

    /**
     * Determine what attributes should be selected. This helps account for all attributes being selected both within
     * and out of the context of a schema.
     *
     * @param array $attributes
     * @param LdapObjectSchema|null $schema
     * @return array
     */
    protected function getSelectedQueryAttributes(array $attributes, LdapObjectSchema $schema = null)
    {
        // Interpret a single wildcard as only schema attributes.
        if ($schema && !empty($attributes) && $attributes[0] == '*') {
            $attributes = array_keys($schema->getAttributeMap());
        // Interpret a double wildcard as all LDAP attributes even if they aren't in the schema file.
        } elseif ($schema && !empty($attributes) && $attributes[0] == '**') {
            $attributes = ['*'];
        }

        return $attributes;
    }

    /**
     * @param array $attributes
     * @param null|string $alias
     * @return array
     */
    protected function getAttributesForAlias(array $attributes, $alias)
    {
        $toSelect = [];

        foreach ($attributes as $attribute) {
            list($attrAlias, $attrSelect) = LdapUtilities::getAliasAndAttribute($attribute);
            if (!$attrAlias || $attrAlias == $alias) {
                $toSelect[] = $attrSelect;
            }
        }

        return $toSelect;
    }


    /**
     * Performs the logic needed to determine what attributes were actually selected, or should be selected, when going
     * to LDAP and whether they should be returned as schema translated names.
     *
     * @param array $attributes
     * @param bool
     * @param LdapObjectSchema|null $schema
     * @param null|string $alias
     * @return array
     */
    protected function getAttributesToLdap(array $attributes, $translate, LdapObjectSchema $schema = null, $alias = null)
    {
        // First determine if this was some sort of wildcard selection
        $attributes = $this->getSelectedQueryAttributes($attributes, $schema);
        // This will return only the attributes for the current alias, minus the possible alias prefix.
        if ($schema) {
            $attributes = $this->getAttributesForAlias($attributes, $alias);
        }
        // If we still have an empty array here, then fill it with the defaults because nothing was selected specifically.
        if ($schema && empty($attributes)) {
            $attributes = $schema->getAttributesToSelect();
        }
        // At this point we add any orderBy attributes that are not being specifically selected already.
        if (!empty($this->orderBy)) {
            $attributes = $this->mergeOrderByAttributes($attributes, $alias);
        }
        
        if ($schema && $translate) {
            $newAttributes = [];
            foreach ($attributes as $attribute) {
                $newAttributes[] = $schema->getAttributeToLdap($attribute);
            }
            $attributes = $newAttributes;
        }

        return $attributes;
    }

    /**
     * A wildcard selection can either be '*' or '**' depending on the context of the call (with or without a schema).
     *
     * @return bool
     */
    protected function isWildCardSelection()
    {
        return (count($this->operation->getAttributes()) === 1 && ($this->operation->getAttributes()[0] == '*' || $this->operation->getAttributes()[0] == '**'));
    }
}
