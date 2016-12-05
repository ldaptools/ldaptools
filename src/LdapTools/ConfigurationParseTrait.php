<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools;

use LdapTools\Exception\ConfigurationException;

/**
 * A trait to reuse some common aspects of parsing configuration keys for both the main and domain configurations.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait ConfigurationParseTrait
{
    /**
     * Parses a YAML config section and sends it back as the correct config values as an array.
     *
     * @param array $config The YAML config section as an array.
     * @param array $existingConfig The config before merging the YAML config.
     * @param array $configMap The YAML to config key map array.
     * @param array $required The required options that must be in the array.
     * @return array The YAML config merged with the existing config.
     * @throws ConfigurationException
     */
    protected function getParsedConfig(array $config, array $existingConfig, array $configMap, array $required)
    {
        $parsedConfig = [];

        foreach ($config as $key => $value) {
            $cfgKey = strtolower($key);
            if (!isset($configMap[$cfgKey])) {
                throw new ConfigurationException(
                    sprintf('Option "%s" not recognized.', $key)
                );
            }
            $parsedConfig[$configMap[$cfgKey]] = $value;
        }

        if (!$this->isParsedConfigValid($parsedConfig, $required)) {
            $needed = [];
            foreach ($required as $option) {
                $needed[] = array_search($option, $configMap);
            }
            throw new ConfigurationException(
                sprintf('Some required configuration options are missing. Required: %s', implode(', ', $needed))
            );
        }
        $existingConfig = array_merge($existingConfig, $parsedConfig);

        return array_filter($existingConfig, function ($v) {
            return $v !== null && $v !== '';
        });
    }

    /**
     * Checks whether all required values for the configuration have been set after the merge.
     *
     * @param array $config
     * @param array $required
     * @return bool
     */
    protected function isParsedConfigValid(array $config, array $required)
    {
        $inConfig = count(array_intersect_key(array_flip($required), array_filter($config)));

        return $inConfig === count($required);
    }

    /**
     * Given a config that has been parsed to what the config values should be, call the setters to make
     * sure all values are validated by any additional logic in the setters.
     *
     * @param array $config
     */
    protected function setParsedConfig(array $config)
    {
        foreach ($config as $key => $value) {
            $setter = 'set'.ucfirst($key);
            $this->$setter($value);
        }
    }
}
