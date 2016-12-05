<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Resolver;

use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Exception\LogicException;
use LdapTools\Utilities\MBString;

/**
 * Iterates over the attributes to process and replace parameter values in the required order based on dependencies.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ParameterResolver
{
    /**
     * The marker that goes on each end of a parameter.
     */
    const PARAM_MARKER = '%';

    /**
     * @var array All of the attributes to iterate through.
     */
    protected $attributes = [];

    /**
     * @var array Any explicitly set parameters.
     */
    protected $parameters = [];

    /**
     * @var array Contains the attribute name with an array of parameters it requires.
     */
    protected $requirements = [];

    /**
     * @var array Parameters that have been resolved to their final values.
     */
    protected $resolvedParameters = [];

    /**
     * @param array $attributes
     * @param array $parameters
     */
    public function __construct(array $attributes, array $parameters)
    {
        $this->attributes = $attributes;
        $this->parameters = $parameters;
    }

    /**
     * Gets the attributes with all of the parameters resolved.
     *
     * @return array
     */
    public function resolve()
    {
        if (!empty($this->resolvedParameters)) {
            return $this->attributes;
        }
        $this->evaluateRequirements($this->attributes);
        $this->resolveAllParameters();

        return $this->attributes;
    }

    /**
     * Check whether the value has any parameters in it.
     *
     * @param array|string $value
     * @return bool
     */
    public static function hasParameters($value)
    {
        return (bool) self::getParametersInValue($value);
    }

    /**
     * Iterates over each requirement to resolve the parameters it contains.
     */
    protected function resolveAllParameters()
    {
        foreach ($this->requirements as $attribute => $parameters) {
            // This parameter may have already been resolved as part of a dependency
            if (!in_array($attribute, $this->resolvedParameters)) {
                $this->resolveParametersForAttribute($attribute, $parameters);
            }
        }
    }

    /**
     * Given a specific attribute with parameters, this will resolve it while first resolving any dependent attributes
     * that first need to be resolved.
     *
     * @param $attribute
     * @param array $parameters
     */
    protected function resolveParametersForAttribute($attribute, array $parameters)
    {
        $children = $this->getDependentAttributes($parameters);

        if (!empty($children)) {
            foreach ($children as $child) {
                $this->resolveParametersForAttribute($child, $this->requirements[$child]);
            }
        }

        $this->doResolveParametersForAttribute($attribute, $parameters);
        $remainingParameters = self::getParametersInValue($this->attributes[$attribute]);

        // A second pass may be required for attributes it depended on that contained parameters not based on attributes
        if (!empty($remainingParameters)) {
            $this->doResolveParametersForAttribute($attribute, $remainingParameters);
        }

        $this->resolvedParameters[] = $attribute;
    }

    /**
     * Sets the value for an attribute with the specified parameters.
     *
     * @param string $attribute
     * @param array $parameters
     */
    protected function doResolveParametersForAttribute($attribute, $parameters)
    {
        $this->attributes[$attribute] = $this->getValueForParameters(
            $parameters,
            $this->attributes[$attribute],
            $this->attributes
        );
    }

    /**
     * Checks for required parameter attributes that depend on other parameter attributes. Returns an array of
     * of dependencies.
     *
     * @param array $parameters
     * @return array
     */
    protected function getDependentAttributes($parameters)
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            if (isset($this->requirements[$parameter]) && !in_array($parameter, $this->resolvedParameters)) {
                $dependencies[] = $parameter;
            }
        }

        return $dependencies;
    }

    /**
     * Check all attributes for what parameters they require. This populates the '$this->requirements' array. It will
     * then iterate back over the requirements to check for issues.
     *
     * @param array $attributes
     */
    protected function evaluateRequirements(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $value = is_array($value) ? $value : [$value];
            $this->setRequirementsForAttribute($attribute, $value);
        }

        foreach ($this->requirements as $attribute => $parameters) {
            $this->checkForCircularDependency($attribute, $parameters);
        }
    }

    /**
     * Given an attribute, set what parameters it requires.
     *
     * @param string $attribute
     * @param array $value
     */
    protected function setRequirementsForAttribute($attribute, array $value)
    {
        $parameters = [];

        foreach ($value as $attrValue) {
            $parameters = array_filter(array_merge(
                $parameters,
                self::getParametersInValue($attrValue)
            ));
        }

        if (!empty($parameters)) {
            $this->requirements[$attribute] = $parameters;
        }
    }

    /**
     * Given an attribute value get all parameters it expects.
     *
     * @param string|array $value
     * @return array
     */
    protected static function getParametersInValue($value)
    {
        $parameters = [];
        $regex = '/'.self::PARAM_MARKER.'(.*?)'.self::PARAM_MARKER.'/';
        $value = is_array($value) ? $value : [$value];

        foreach ($value as $attrValue) {
            if (is_string($attrValue) && preg_match_all($regex, $attrValue, $matches) && isset($matches[1])) {
                $parameters = array_merge($parameters, $matches[1]);
            }
        }

        return $parameters;
    }

    /**
     * Given an attribute with parameter dependencies, check if any of them will become circular.
     *
     * @param string $parent
     * @param array $parentParameters
     */
    protected function checkForCircularDependency($parent, array $parentParameters)
    {
        foreach ($this->requirements as $attribute => $parameters) {
            if (in_array($parent, $parameters) && in_array($attribute, $parentParameters)) {
                throw new LogicException(sprintf(
                    'Circular parameter dependency detected. Parameters "%s" and "%s" depend on each other.',
                    $parent,
                    $attribute
                ));
            }
        }
    }

    /**
     * Takes all parameters (%username%, %someParameter%) within an attribute value and first checks for explicitly
     * set values for the parameter, then checks to see if the parameter name is a different attribute. If found it
     * takes the value either explicitly set or of the other attribute and replaces it within the original attribute.
     *
     * @param array $parameters All of the parameters found within the value.
     * @param array|string $original The original value for the attribute, containing the parameters.
     * @param array $attributes  All of the attributes being sent to LDAP.
     * @return string The attribute value after the passed parameters have been set.
     */
    protected function getValueForParameters(array $parameters, $original, array $attributes)
    {
        $wasArray = is_array($original);
        $original = $wasArray ? $original : [$original];

        foreach (array_keys($original) as $index) {
            foreach ($parameters as $parameter) {
                $value = '';
                // Explicitly set parameters values will take precedence
                if (array_key_exists(MBString::strtolower($parameter), MBString::array_change_key_case($this->parameters))) {
                    $value = array_change_key_case($this->parameters)[MBString::strtolower($parameter)];
                } elseif (array_key_exists(MBString::strtolower($parameter), MBString::array_change_key_case($attributes))) {
                    $value = MBString::array_change_key_case($attributes)[MBString::strtolower($parameter)];
                }
                if (is_array($value) && count($value) !== 1) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot use a multi-valued attribute "%s" as a parameter.',
                        $parameter
                    ));
                }
                $value = is_array($value) && count($value) == 1 ? reset($value) : $value;
                $original[$index] = preg_replace("/" . self::PARAM_MARKER . $parameter . self::PARAM_MARKER . "/", $value, $original[$index]);
            }
        }

        return $wasArray ? $original : $original[0];
    }
}
